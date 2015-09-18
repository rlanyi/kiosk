<?php
require_once __DIR__.'/../vendor/autoload.php';

use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Kiosk\PlaylistService;
use Kiosk\Validator\Constraints\Security;

$app = new Silex\Application();

/**  Application */
$app->register(new SessionServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

/**  Config */
$app['config'] = array(
    'web_dir' => ''
);
$app['locale'] = 'hu';

/** Controller */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->get('/', function() use($app) {
  return $app->redirect('/playlist');
});

$app->match('/playlist', function (Request $request) use ($app) {

  $app->register(new FormServiceProvider());
  $app->register(new TranslationServiceProvider());
  $app->register(new ValidatorServiceProvider(), array(
      'translator.messages' => array(),
  ));

  PlaylistService::init();
  $pldata = array();
  foreach (PlaylistService::getData() as $pld) {
    $pldata[$pld['id']] = $pld['name'];
  }

  $form = $app['form.factory']->createBuilder('form')
    ->add('playlist', 'choice', array(
      'choices' => $pldata,
      'expanded' => true,
      'constraints' => ($request->request->get('stop')) ? array() : array(new Assert\Choice(array_keys($pldata)), new Assert\NotNull()),
    ))
    ->add('security', 'password', array(
      'constraints' => array(new Security()),
      'attr' => array('placeholder' => 'Biztonsági kód'),
      'label' => 'Biztonsági kód',
    ))
    ->getForm();

  if ('POST' == $request->getMethod()) {
    $form->bind($request);

    if ($form->isValid()) {
      $data = $form->getData();

      $saved = false;
      if ($request->request->get('stop')) {
        PlaylistService::disablePlaylist(true);
        $saved = PlaylistService::savePlaylist();
      }

      if ($request->request->get('play')) {
        PlaylistService::disablePlaylist(true);
        PlaylistService::enablePlaylist($data['playlist']);
        $saved = PlaylistService::savePlaylist();
      }

      if ($saved) {
        $daemon = new Kiosk\Daemon();
        $daemon->reload();
      }

//      return $app->redirect('/playlist');
    }

  }

  // display the form
  return $app['twig']->render('playlist.twig', array('form' => $form->createView(), 'permission_error' => !PlaylistService::isWritable()));
});

return $app;
