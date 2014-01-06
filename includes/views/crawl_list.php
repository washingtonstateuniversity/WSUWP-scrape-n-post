<?php 
if(isset($urls)){
	var_dump($urls);
}

 ?>
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
     
        
        <input type="submit" id="scrape-findlinks" name="scrape_test_crawler" class="button-primary" value="<?php echo _e('Test Crawler'); ?>">
       	<input type="submit" id="scrape-findlinks" name="scrape_findlinks" class="button" value="<?php echo _e('Crawl for links'); ?>">
    </form>
  </div><div class="clr"></div>
</div>

<div id="catpdf-wrap" class="wrap">
  <div class="icon32" id="icon-tools"><br>
  </div>
  <h2><?php _e('Crawler Quque found urls'); ?></h2>
  <form method="post">
    <?php echo $table;?>
  </form>
</div>