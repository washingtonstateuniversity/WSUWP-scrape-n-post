<div id="catpdf-wrap" class="wrap">
  <div class="icon32" id="icon-tools"><br>
  </div>
  <h2>
    <?php _e('Add Templates'); ?>
  </h2>
  <?php if( isset($message) && $message!='' ) echo $message; ?>
  <p class="desc-text"> </p>
  <div id="form-wrap">
    <form method="post">
      <input type="hidden" id="templateid" name="templateid" value="<?php echo ( isset( $on_edit )?$on_edit->template_id:'' );?>">
      <div class="actions-wrap">
        <input type="submit" id="catpdf-save" name="catpdf_save" class="button-primary" value="<?php _e('Save Template');?>">
      </div>
      <div class="clr"></div>
      <div class="field-wrap">
        <div class="field">
          <label><?php echo _e( "Template Name" ); ?></label>
          <input type="text" id="templatename" name="templatename" value="<?php echo ( isset( $on_edit )?$on_edit->template_name:'' );?>" />
        </div>
        <div class="note"> <span>(
          <?php _e("Provide template title."); ?>
          )</span> </div>
      </div>
      <div class="clr"></div>
      <div class="field-wrap">
        <div class="field">
          <label>
            <?php _e('Template Description');?>
          </label>
          <textarea class="ta_standard" name="description"><?php echo ( isset( $on_edit )?$on_edit->template_description:'' );?></textarea>
        </div>
        <div class="note"> <span>(
          <?php _e("Provide a short description of this template."); ?>
          )</span> </div>
      </div>
      <div class="actions-wrap">
        <input type="submit" id="catpdf-save" name="catpdf_save" class="button-primary" value="<?php _e('Save Template');?>">
      </div>
    </form>
  </div>
</div>
