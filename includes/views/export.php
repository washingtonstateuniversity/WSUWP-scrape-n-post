<div id="scrape-wrap" class="wrap">
  <div class="icon32" id="icon-tools"><br>
  </div>
  <h2><?php echo SCRAPE_NAME; ?></h2>
  <?php if( isset($message) && $message!='' ) echo $message; ?>
  <p class="desc-text">
    <?php _e( "" );?>
  </p>
  <div id="form-wrap">
    <form id="scrape_form" method="post" action="<?php echo $option_url;?>">
		<label>Url <input type="url" name="scrape_url" /> </label>
      <p class="submit">
        <input type="submit" id="scrape-findlinks" name="scrape_findlinks" class="button-primary" value="<?php echo _e('Download'); ?>">
      </p>
    </form>
  </div>
</div>