
<div class="mta-leadgen-metabox mta-leadgen-gated-content-metabox">
  <?php
  foreach($form_items as $key => $field) {
    $wrapper_class = isset($field['wrapper_class']) ? $field['wrapper_class'] : 'meta-input';
    $field_item = isset($field['field_item']) ? $field['field_item'] : '';
    $field_desc = isset($field['field_desc']) ? '<div class="description"><em>'.$field['field_desc'].'</em></div>' : '';
    $field_label = isset($field['field_label']) ? $field['field_label'] : '';

    print "<div class='$wrapper_class'>$field_label $field_item $field_desc</div>";
  }
  ?>
</div>
