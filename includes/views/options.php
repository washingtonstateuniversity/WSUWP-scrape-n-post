<?php

//var_dump($scrape_options);

?>

<div id="scrape-wrap" class="wrap">
  <div class="icon32" id="icon-options-general"><br>
  </div>
  <h2><?php echo SCRAPE_NAME.' '.__('Options'); ?></h2>
  <?php if( isset($message) && $message!='' ) echo $message; ?>
  <div id="form-wrap">
    <form id="scrape_form" method="post">
      <div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Crawl Depth"); ?> </label>
          <input type="text" name="crawl_depth" id="crawl_depth"  value="<?php echo $scrape_options['crawl_depth']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Set the number to the deth you whish to crawl a site.  If the site is big 100-200 is a good choice.  Make sure you have the php max-* limits set to account for a deep crawl before running."); ?>
          )</span> </div>
      </div>


      <div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Useragent string"); ?> </label>
          <input type="text" name="useragent" id="scrape_useragent"  value="<?php echo $scrape_options['useragent']; ?>"  class="large-text code"/>
        </div>
        <div class="note"> <span>(
          <?php _e("Default useragent header to identify yourself when crawling sites."); ?>
          )</span> </div>
      </div>
      <div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Timeout (in seconds)"); ?> </label>
          <input type="text" name="timeout" id="scrape_timeout"  value="<?php echo $scrape_options['timeout']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Default timeout interval in seconds for cURL or Fopen. Larger interval might slow down your page."); ?>
          )</span> </div>
      </div>
      
      
	<div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Add posts on crawl" ); ?>  </label>
          <select name="add_post_on_crawl">
            <option <?php selected('1', $scrape_options['add_post_on_crawl']); ?> value="1"> <?php _e('Yes');?> </option>
            <option <?php selected('0', $scrape_options['add_post_on_crawl']); ?> value="0"> <?php _e('No');?> </option>
          </select>
        </div>
        <div class="note"> <span>(
          <?php _e("This can take a bit, make sure php ini is set up for long running scripts"); ?>
          )</span> </div>
	</div>       
      
	<div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Apply Xdebug fix" ); ?>  </label>
          <select name="xdebug_fix">
            <option <?php selected('1', $scrape_options['xdebug_fix']); ?> value="1"> <?php _e('Yes');?> </option>
            <option <?php selected('0', $scrape_options['xdebug_fix']); ?> value="0"> <?php _e('No');?> </option>
          </select>
        </div>
        <div class="note"> <span>(
          <?php _e("Xdebug is not with out it's own bugs.  Such is life, but this will set the `xdebug.max_nesting_level` so that it follows your php.ini settings"); ?>
          )</span> </div>
	</div>   
       <div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Timeout (in seconds)"); ?> </label>
          <input type="text" name="time_limit" id="scrape_time_limit"  value="<?php echo $scrape_options['time_limit']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Over write [if possible], php's time_limit"); ?>
          )</span> </div>
      </div>     
      
      
        <div class="field-wrap">
        <div class="field">
          <label> <?php _e( "php Memory Limit (in MB)"); ?> </label>
          <input type="text" name="memory_limit" id="scrape_memory_limit"  value="<?php echo $scrape_options['memory_limit']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Over write [if possible], php's memory_limit. NOTE: setting to 0 or blank disables, where setting to `-1` assigns unlimited."); ?>
          )</span> </div>
      </div>     
           

      
      
      
      
      <input type="hidden" name="action" value="update" />
      <p class="submit">
        <input type="submit" name="scrape_save_option" class="button-primary" value="Save Changes">
      </p>
    </form>
  </div>
</div>