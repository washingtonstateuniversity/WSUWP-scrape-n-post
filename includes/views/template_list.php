<div id="catpdf-wrap" class="wrap">
  <div class="icon32" id="icon-tools"><br>
  </div>
  <h2>
    <?php _e('Manage Templates'); ?>
    <a class="add-new-h2" href="<?php menu_page_url( 'scrape-add-template' , true );?>">
    <?php _e( 'Add New' );?>
    </a></h2>
  <?php if( isset($message) && $message!='' ) echo $message; ?>
  <form method="post">
    <?php echo $table;?>
  </form>
</div>