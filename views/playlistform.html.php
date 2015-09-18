  <h2>Lejátszási lista váltása</h2>
  <p>Válaszd ki, hogy melyik listát szeretnéd indítani, majd kattints a Mehet gombra. Az éppen futó lista lejátszása megszakad. A Megállít gomb azonnal leállítja a lejátszást.</p>

  <form method="post" name="playlistform" novalidate="novalidate">
    <div class="control-group">
          Lejátszási listák

      <div class="controls">
        <?php foreach ($params['playlist'] as $key => $name): ?>
        <label for="form_playlist_<?php echo $key ?>" class="radio required"><input type="radio" id="form_playlist_<?php echo $key ?>" name="form[playlist]" required="required" value="<?php echo $key ?>"><?php echo $name ?>
        </label>
        <?php endforeach; ?>
      </div>

    </div>

    <button type="submit" name="play" value="play" class="btn btn-primary">Mehet</button>
    <button type="submit" name="stop" value="stop" class="btn btn-danger">Megállít</button>
    <button type="submit" name="refresh" value="stop" class="btn btn-link">Lista újratöltése</button>
  </form>
