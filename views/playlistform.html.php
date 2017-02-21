  <h2>Lejátszási lista váltása</h2>
  <p>Válaszd ki, hogy melyik listát szeretnéd indítani, majd kattints a <strong>Lejátszás</strong> gombra. Az éppen futó lista lejátszása megszakad. 
     A <strong>Megállít</strong> gomb azonnal leállítja a lejátszást. Az <strong>Újratöltése</strong> gomb megállítja a lejátszást és újratölti a lejátszási listákat.</p>

  <form method="post" name="playlistform" novalidate="novalidate">

    <button type="submit" name="play" value="play" data-loading-text="Lejátszás..." class="btn btn-primary">Lejátszás</button>
    <button type="submit" name="stop" value="stop" data-loading-text="Megállít..." class="btn btn-danger">Megállít</button>
    <button type="submit" name="refresh" value="stop" data-loading-text="Újratöltés..." class="btn btn-success">Újratöltés</button>

    <div class="control-group">
      <h3>Lejátszási listák</h3>

      <div class="controls">
        <?php foreach ($params['playlist'] as $key => $name): ?>
        <label for="form_playlist_<?php echo $key ?>" class="radio required"><input type="radio" id="form_playlist_<?php echo $key ?>" name="form[playlist]" required="required" value="<?php echo $key ?>"><?php echo $name ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
  </form>

