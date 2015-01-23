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
			  <?php _e("Set the number to the depth you whish to crawl a site.  If the site is big 100-200 is a good choice.  Make sure you have the php max-* limits set to account for a deep crawl before running."); ?>
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
			  <label> <?php _e( "Use Post Type" ); ?>  </label>
			  <select name="add_post_on_crawl">
			  <?php foreach($post_type as $key=>$val):?>
			  	<option <?php selected($val, $scrape_options['post_type']); ?> value="1"> <?=$val?> </option>
			  <?php endforeach; ?>
			  </select>
			</div>
			<div class="note"> <span>(
			  <?php _e("On a run this post type will be used to match up and create the shadow copy for."); ?>
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
          <label> <?php _e( "Page Timeout (in seconds)"); ?> </label>
          <input type="text" name="timeout" id="scrape_timeout"  value="<?php echo $scrape_options['timeout']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Default timeout interval in seconds for cURL or Fopen. Larger interval might slow down your page."); ?>
          )</span> </div>
      </div>
       <div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Limit pages scraped"); ?> </label>
          <input type="text" name="limit_scraps" id="scrape_limit_scraps"  value="<?php echo $scrape_options['limit_scraps']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Default limit is infinite, but this could cause issues, one way to get around a server that is not ok with it's pages being crawled is to do short runs."); ?>
          )</span> </div>
      </div>     
        <div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Scraping interval (in seconds)"); ?> </label>
          <input type="text" name="interval" id="scrape_interval"  value="<?php echo $scrape_options['interval']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Default limit is 1, but this could cause issues.  To slow it down, just increse the number.  5 should be more then enough to please any server."); ?>
          )</span> </div>
      </div>      
	  
	  
	  
	  
        <div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Retry on failed scrape interval (in seconds)"); ?> </label>
          <input type="text" name="retry_interval" id="scrape_retry_interval"  value="<?php echo $scrape_options['retry_interval']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Default limit is 2"); ?>
          )</span> </div>
      </div>  
	  
        <div class="field-wrap">
        <div class="field">
          <label> <?php _e( "Retry on failed scrape Limit"); ?> </label>
          <input type="text" name="retry_limit" id="scrape_retry_limit"  value="<?php echo $scrape_options['retry_limit']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Default limit of retries is 3"); ?>
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
          <input type="text" name="timeout_limit" id="scrape_timeout_limit"  value="<?php echo $scrape_options['timeout_limit']; ?>" class="small-text code" />
        </div>
        <div class="note"> <span>(
          <?php _e("Over write [if possible], php's execution time limit"); ?>
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