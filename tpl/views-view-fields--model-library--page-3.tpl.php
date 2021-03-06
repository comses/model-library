<?php

/**
 * @file
 * Default simple view template to all the fields as a row.
 *
 * - $view: The view in use.
 * - $fields: an array of $field objects. Each one contains:
 *   - $field->content: The output of the field.
 *   - $field->raw: The raw data for the field, if it exists. This is NOT output safe.
 *   - $field->class: The safe class id to use.
 *   - $field->handler: The Views field handler object controlling this field. Do not use
 *     var_export to dump this object, as it can't handle the recursion.
 *   - $field->inline: Whether or not the field should be inline.
 *   - $field->inline_html: either div or span based on the above flag.
 *   - $field->wrapper_prefix: A complete wrapper containing the inline_html to use.
 *   - $field->wrapper_suffix: The closing tag for the wrapper.
 *   - $field->separator: an optional separator that may appear before a field.
 *   - $field->label: The wrap label text to use.
 *   - $field->label_html: The full HTML of the label to use including
 *     configured element type.
 * - $row: The raw result object from the query, with all data it fetched.
 *
 * @ingroup views_templates
 */
global $user;
global $base_url;
global $base_path;

$modelnid = arg(1);
$version = arg(3);

// Include the CTools tools that we need.
ctools_include('ajax');
ctools_include('modal');
?>

<div class="model-<?php print $modelnid; if ($fields['status']->raw == 0) print ' node-unpublished'; ?>">
<table style="margin: 0;" border="0" width="100%">
  <tr>
    <td colspan=3>
<?php 
$node = node_load($modelnid);
$uid = $fields['uid']->raw;

if ($fields['status']->raw == 1) {
  print '      <div class="model-authors">';
  
  // Print Model Authors
  if (isset($node->field_model_author[LANGUAGE_NONE])) {
    foreach ($node->field_model_author[LANGUAGE_NONE] as $key)
      $author_refs[] = $key['value'];

    $authors = entity_load('field_collection_item', $author_refs);

    $i = 0;
    foreach ($authors as $index => $instance) {
      $authorfirst  = $instance->field_model_authorfirst[LANGUAGE_NONE][0]['value'];
      if (isset($instance->field_model_authormiddle[LANGUAGE_NONE][0]['value']))
        $authormiddle = $instance->field_model_authormiddle[LANGUAGE_NONE][0]['value'];
      else
        $authormiddle = '';
      $authorlast   = $instance->field_model_authorlast[LANGUAGE_NONE][0]['value'];

      if ($i > 0) print ', '; print check_plain($authorfirst) . ' '; if ($authormiddle > '') print check_plain($authormiddle) . ' '; print check_plain($authorlast);
      $i++;
    }
  }
  else {
    // Load Profile
    $profile2 = profile2_load_by_user($uid, 'main');

    if (isset($profile2->field_profile2_lastname[LANGUAGE_NONE][0])) {
        $field = field_view_value('profile2', $profile2, 'field_profile2_firstname', $profile2->field_profile2_firstname[LANGUAGE_NONE][0]);
        $output = drupal_render($field);

        if (isset($profile->field_profile2_middlename[LANGUAGE_NONE][0])) {
          $field = field_view_value('profile2', $profile2, 'field_profile2_middlename', $profile2->field_profile2_middlename[LANGUAGE_NONE][0]);
          $output .= ' ' . drupal_render($field);
        }

        $field = field_view_value('profile2', $profile2, 'field_profile2_lastname', $profile2->field_profile2_lastname[LANGUAGE_NONE][0]);
        $output .= ' ' . drupal_render($field);
    }
    print $output;
  }

  print '      </div>';
}
?>
    </td>
  </tr>
  <tr>
    <td>
      <div class="model-block">
<?php if ($fields['status']->raw == 0): ?>
        <div class="unpublished">Unpublished</div>
<?php endif; ?>
<?php if ($fields['status']->raw == 1): ?>
        <div class="model-author1">Submitted By: <?php 

        if (isset($profile2->field_profile2_lastname[LANGUAGE_NONE][0])) {
          $field = field_view_value('profile2', $profile2, 'field_profile2_firstname', $profile2->field_profile2_firstname[LANGUAGE_NONE][0]);
          $output = substr(drupal_render($field), 0, 1);

          if (isset($profile->field_profile2_middlename[LANGUAGE_NONE][0])) {
            $field = field_view_value('profile2', $profile2, 'field_profile2_middlename', $profile2->field_profile2_middlename[LANGUAGE_NONE][0]);
            $output .= substr(drupal_render($field), 0, 1);
          }

          $output .= ' ';
          $field = field_view_value('profile2', $profile2, 'field_profile2_lastname', $profile2->field_profile2_lastname[LANGUAGE_NONE][0]);
          $output .= drupal_render($field);

        }
        else {
          $output = $fields['name']->content;
        }

        if (in_array('comses member', array_values($user->roles)) || in_array('administrator', array_values($user->roles))) 
          $output = '<a href="/user/' . $fields['uid']->raw . '">' . $output . '</a>';

        print $output; ?>
        </div>
<?php endif; ?>
        <div class="model-date">Submitted: <?php print date('M j, Y', $node->created); ?></div>
        <div class="model-date model-updated-date">Last Updated: <?php print date('M j, Y', $node->changed); ?></div>
<?php
      // Code file download count, all-time
      $sql = "SELECT SUM(downloads) AS downloads FROM (SELECT COUNT(dc.fid) AS downloads FROM download_count dc WHERE dc.fid IN (SELECT DISTINCT field_modelversion_code_fid AS code_fid FROM node n LEFT JOIN field_data_field_modelversion_code fmvc ON n.vid = fmvc.revision_id LEFT JOIN field_data_field_modelversion_model fmvm ON n.vid = fmvm.revision_id WHERE (n.type in ('modelversion')) AND (n.status = 1) AND (fmvm.field_modelversion_model_nid = :nid))) dl";
      $all_dls = db_query($sql, array(':nid' => $modelnid))->fetchField(); 

      // Code download count, last 3 months
      $sql = "SELECT SUM(downloads) AS downloads FROM (SELECT COUNT(dc.fid) AS downloads FROM download_count dc WHERE dc.fid IN (SELECT DISTINCT field_modelversion_code_fid AS code_fid FROM node n LEFT JOIN field_data_field_modelversion_code fmvc ON n.vid = fmvc.revision_id LEFT JOIN field_data_field_modelversion_model fmvm ON n.vid = fmvm.revision_id WHERE (n.type in ('modelversion')) AND (n.status = 1) AND (fmvm.field_modelversion_model_nid = :nid) AND (FROM_UNIXTIME(dc.timestamp) > (now() - INTERVAL 3 MONTH)))) dl";
      $month_dls = db_query($sql, array(':nid' => $modelnid))->fetchField(); 
?>
  <div class="model-downloads"><?php print $all_dls; if ($all_dls == 1) print ' Download'; else print ' Downloads'; print ' ('. $month_dls; if ($month_dls == 1) print ' Download in the last 3 months)'; else print ' Downloads in the last 3 months)'; ?></div>
      </div>
    </td>
    <td>
<?php
    $sql = "SELECT mra.statusid "
          ."FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
          ."WHERE mr.model_nid = :nid";
    $statusid = db_query($sql, array(':nid' => $modelnid))->fetchField();

    if ($statusid == 60)
      print '      <div class="model-badge"><img src="/files/images/certified-badge-big.png" /></div>';
?>
    </td>
    <td>
      <?php
        if ($fields['status']->raw == 0 && (in_array('administrator', array_values($user->roles)) || $user->uid == $fields['uid']->raw)) {
          print ctools_modal_text_button('Publish Model', 'model/'. $modelnid .'/nojs/publish', 'Publish model', 'ctools-modal-modellibrary-modal-medium model-button');
        }

        if ($fields['field_modelversion_number']->content != modellibrary_helper_get_max_versionnum($modelnid)) {
          print '<a class="model-button" href="'. url('model/'. $modelnid) .'">Latest Version</a>';
        }
      ?>
    </td>
  </tr>
  <tr>
    <td colspan=4>
      <div>
<?php 
  // Render the Model Average Rating
  $argument = $modelnid;
  print views_embed_view('model_comments', 'panel_pane_3', $argument);
?>
      </div>
    </td>
  </tr>
</table>
<div class="hrline" style="margin: 5px 0 0 0;"></div>
<table style="margin: 0;" border="0" width="100%">
  <tr valign="top" style="padding-top: 10px;">
    <td>
      <div class="model-region1">
        <div class="model-body">
          <?php print $fields['body']->content; ?>
        </div>

<?php $field_items = field_get_items('node', $node, 'taxonomy_vocabulary_6'); ?>

<?php if (isset($field_items[0])) : ?>
        <div class="model-tags-block">
          <div class="model-block-title">Keywords:</div>
          <div class="model-tags">

<?php 
  foreach ($field_items as $id => $result) :
    $field = drupal_render(field_view_value('node', $node, 'taxonomy_vocabulary_6', $result));
    print '<span class="tag"><a class="tag" href="' . url('models/' . strip_tags($field) .'/keyword') . '">' . strip_tags($field) .'</a></span>';
  endforeach;
?>

          </div>
        </div>
<?php endif; # endif field_items ?>
<?php if (isset($fields['field_model_publication_text'])) : ?>
        <div class="model-block model-publication-block">
          <div class="model-block-title model-publication-title">This model is associated with a publication:</div>
          <div class="model-publication-text"><p><?php print $fields['field_model_publication_text']->content; ?></p></div>
        </div>
<?php endif; ?>

<?php if ($fields['field_model_replicated']->content == 1) : ?>
        <div class="model-block model-replication-block">
          <div class="model-block-title model-replication-title">This is a replication of a previously published model:</div>
          <div class="model-replication-text"><p><?php print $fields['field_model_reference']->content; ?></p></div>
        </div>
<?php endif; ?>
<?php if ($fields['status']->raw == 1) : ?>
        <div class="model-block model-citation-block">
          <div class="model-block-title model-citation-title">Cite This Model:</div>
            <div class="model-citation-text">
<?php
if (isset($authors)) :
  $i = 0;
  foreach ($authors as $index => $instance) :
    $authorfirst  = $instance->field_model_authorfirst[LANGUAGE_NONE][0]['value'];
    if (isset($instance->field_model_authormiddle[LANGUAGE_NONE][0]['value']))
      $authormiddle = $instance->field_model_authormiddle[LANGUAGE_NONE][0]['value'];
    else
      $authormiddle = '';
    $authorlast   = $instance->field_model_authorlast[LANGUAGE_NONE][0]['value'];

    if ($i > 0) print ', '; print check_plain($authorlast) . ', ' . check_plain($authorfirst); if ($authormiddle > '') print ' ' . check_plain($authormiddle);
    $i++;
  endforeach;
else: // if authors
    if (isset($profile2->field_profile2_lastname[LANGUAGE_NONE][0])) {
        $field = field_view_value('profile2', $profile2, 'field_profile2_lastname', $profile2->field_profile2_lastname[LANGUAGE_NONE][0]);
        $output = drupal_render($field) . ', ';

        $field = field_view_value('profile2', $profile2, 'field_profile2_firstname', $profile2->field_profile2_firstname[LANGUAGE_NONE][0]);
        $output .= drupal_render($field);

        if (isset($profile->field_profile2_middlename[LANGUAGE_NONE][0])) {
        $field = field_view_value('profile2', $profile2, 'field_profile2_middlename', $profile2->field_profile2_middlename[LANGUAGE_NONE][0]);
        $output .= ' ' . drupal_render($field);
        }
    }
    else {
        $output = $fields['name']->content;
    }
    print $output;
endif; // if authors
?> (<?php print date('Y, F j', $fields['created']->raw); ?>). "<?php print $fields['title']->content; ?>" (Version <?php print $fields['field_modelversion_number']->content; ?>). <em>CoMSES Computational Model Library</em>. Retrieved from: <?php if ($fields['field_model_handle']->content > "") print $fields['field_model_handle']->content; else print $base_url . $base_path .'model/'. $modelnid .'/version/'. $fields['field_modelversion_number']->content; ?>
          </div>
        </div>
      </div>
<?php endif; ?> <!-- endif model published -->
    </td>
    <td width=175>
      <div class="model-region2">
        <div class="model-image">
          <?php print $fields['field_model_image']->content; ?>
        </div>
        <div class="model-video">
          <?php 
          if (isset($fields['field_model_video']->content)) {
            print '<a href="' . url($fields['field_model_video']->content) .'" rel="shadowbox;width=600;height=450"><img width="175" src="/files/images/video_thumbnail.png" /></a>';
          }
          ?>&nbsp;
        </div>
      </div>
    </td>
  </tr>
<?php
    // Generate Model Review status info. Determine the current Review Status Code
    // Lookup the Review
    $sql = "SELECT mra.statusid "
          ."FROM {modelreview} mr INNER JOIN {modelreview_action} mra ON mr.rid = mra.rid AND mr.sid = mra.sid "
          ."WHERE mr.model_nid = :nid";
    $statusid = db_query($sql, array(':nid' => $modelnid))->fetchField();

    switch ($statusid) {
      case 10:
      case 20:
      case 30:
      case 40:
      case 50:

        if (in_array('administrator', array_values($user->roles)) || $user->uid == $fields['uid']->raw) { // also if Certified
          $message = '<div class="model-certification-text"><p>This model is currently under review. To view the review status page, click "Review Status" below.</p></div>';
          $message .= '<div class="model-block"><a class="model-submit-button" href="'. $base_url . $base_path .'model/'. $modelnid .'/review/status">Review Status</a></div>';
        }
        break;

      case 60:
        $message = '<div class="model-certification-text"><p>This model has been Certified that it meets the CoMSES Guidelines for Modeling Best-Practices. Certification involves a review process by which a model is examined to ensure it has been coded and documented according to the community\'s best-practices. For more information on the Model Review process and Certification: <div class="more-link"><a href="/models/reviews/info">Read more</a></div></p></div>';
        break;

      case 70:
        break;

      default:
        if (in_array('administrator', array_values($user->roles)) || $user->uid == $fields['uid']->raw) { // also if Certified
          $message = '<div class="model-certification-text"><p>This model can be reviewed for CoMSES Certification. Certification involves a review process by which a model is examined to ensure it has been coded and documented according to the community\'s best-practices. Click the "Request Review" button below for more information on the Model Certification process.</p></div>';
          $message .= '<div class="model-block"><a class="model-submit-button" href="'. $base_url . $base_path .'model/'. $modelnid .'/review/info">Request Review</a></div>';
        }
    }

    if (isset($message)) {
      print '  <tr>';
      print '    <td colspan=2>';
      print '      <div style="margin: 0;" class="hrline"/>';
      print '    </td>';
      print '  </tr>';
      print '  <tr>';
      print '    <td>';
      print '      <div class="model-region1">';
      print '      <div class="model-section-title">Model Certification</div>';
      print $message;
      print '      </div>';
      print '    </td>';
      print '  </tr>';
    }
?>
<?php if ($fields['status']->raw == 0 || $fields['field_modelversion_number']->content != modellibrary_helper_get_max_versionnum($modelnid)): ?>
  <tr>
    <td colspan=2>
      <div style="margin: 0;" class="hrline"/>
    </td>
  </tr>
  <tr>
    <td>
      <div class="modelstatus">
        <h2>Model Status</h2>
        <?php // if not latest version
        if ($fields['field_modelversion_number']->content != modellibrary_helper_get_max_versionnum($modelnid)): ?>
          <h3>You are viewing an old version of this model with out-of-date file downloads.  To view the latest model version, click the "Latest" button above.<h3>
        <?php endif; ?>

        <?php // if not published and model can be enabled
        if ($fields['status']->raw == 0) :
          print '          <h3>This model is currently unpublished. ';
          if (in_array('administrator', array_values($user->roles)) || $user->uid == $fields['uid']->raw)
            print "To enable it, click the Publish Model button above.";
          print '          </h3>';
        endif; ?> 
      </div>
    </td>
  </tr>
<?php endif; ?>
</table>
<div class="hrline" style="margin: 5px 0 0 0;"></div>
<table style="margin: 0;" border="0" width="100%">
  <tr>
    <td width=50%>
      <div class="model-region3">
        <div class="model-section-title">
          Model Version: <?php print $fields['field_modelversion_number']->content; 
          if ($fields['field_modelversion_number']->content == modellibrary_helper_get_max_versionnum($fields['field_modelversion_model']->content)) print '  [Latest]'; ?>
        </div>
        <div>
<?php
  // Render the Model Average Rating
  $argument1 = $modelnid;
  $argument2 = $fields['field_modelversion_number']->content;
  print views_embed_view('model_comments', 'panel_pane_4', $argument1, $argument2);
?>
        </div>
        <?php if ($fields['body_1']->content > ''): ?>
        <div class="model-block">
          <span class="model-block-title">Version Notes:</span>
          <span class="model-block-text"><p><?php print $fields['body_1']->content; ?></p></span>
        </div>
        <?php endif; ?>

<?php
  $query = "SELECT id, name, address FROM {modellibrary_platform} WHERE id = :id";
  $result = db_query($query, array(':id' => $fields['field_modelversion_platform']->content))->fetchObject();
?>
        <div class="model-block model-version-block">
        <div class="model-version-item">
          <span class="model-block-title">Platform:</span>
          <span class="model-block-text"> <?php 
            if ($fields['field_modelversion_platform']->content == 0) {
              print (isset($fields['field_modelversion_platform_oth']) ? $fields['field_modelversion_platform_oth']->content : '') . " " . (isset($fields['field_modelversion_platform_ver']) ? $fields['field_modelversion_platform_ver']->content : '');
            }
            else {
              print (isset($result->name) ? $result->name : '') . " " . (isset($fields['field_modelversion_platform_ver']) ? $fields['field_modelversion_platform_ver']->content : '');
            } ?></span>
        </div>

<?php
  $query = "SELECT id, name FROM {modellibrary_language} WHERE id = :id";
  $result = db_query($query, array(':id' => $fields['field_modelversion_language']->content))->fetchObject();
?>
        <div class="model-version-item">
          <span class="model-block-title">Programming Language:</span>
          <span class="model-block-text"> <?php
            if ($fields['field_modelversion_language']->content == 0) {
              print (isset($fields['field_modelversion_language_oth']) ? $fields['field_modelversion_language_oth']->content : '') ." ". (isset($fields['field_modelversion_language_ver']) ? $fields['field_modelversion_language_ver']->content : '');
            }
            else {
              print (isset($result->name) ? $result->name : '') . " " . (isset($fields['field_modelversion_language_ver']) ? $fields['field_modelversion_language_ver']->content : '');
            } ?></span>
        </div>

<?php
  $query = "SELECT id, name FROM {modellibrary_os} WHERE id = :id";
  $result = db_query($query, array(':id' => $fields['field_modelversion_os']->content))->fetchObject();
?>
        <div class="model-version-item">
          <span class="model-block-title">Operating System:</span>
          <span class="model-block-text"> <?php
            if ($fields['field_modelversion_os']->content == 0) {
              print (isset($fields['field_modelversion_os_oth']) ? $fields['field_modelversion_os_oth']->content : '') ." ". (isset($fields['field_modelversion_os_ver']) ? $fields['field_modelversion_os_ver']->content : '');
            }
            else {
              print (isset($result->name) ? $result->name : '') . " " . (isset($fields['field_modelversion_os_ver']) ? $fields['field_modelversion_os_ver']->content : '');
            } ?></span>
        </div>

<?php
  $query = "SELECT id, name, address FROM {modellibrary_license} WHERE id = :id";
  $result = db_query($query, array(':id' => $fields['field_modelversion_license']->content))->fetchObject();
?>
        <div class="model-version-item">
          <span class="model-block-title">Licensed Under:</span> 
          <span class="model-block-text"><?php print '<a href="' . $result->address . '">' . $result->name . '</a>'; ?></span>
        </div>
      </div> 
      <div class="model-block">
        <span class="model-block-title">Instructions on Running This Model:</span>
        <?php if ($fields['field_modelversion_runconditions']->content == "")
          print '<span class="model-block-text"> None.</span>';
        else
          print '<div model-block-text>'. $fields['field_modelversion_runconditions']->content .'</div>';
        ?></p>
      </div>
    </td>
    <td width=50%>
      <div class="model-region4">
      <div class="model-section-title">Model Files</div>
      <!--googleoff: all-->
      <div class="model-files">
        <div>
          <span class="label">Code:</span>
          <span class="content"><?php print $fields['field_modelversion_code']->content; ?></span>
        </div>
        <div>
          <span class="label">Docs:</span>
          <span class="content"><?php print $fields['field_modelversion_documentation']->content; ?></span>
        </div>
        <?php if (isset($fields['field_modelversion_dataset'])): ?>
        <div>
          <span class="label">Dataset:</span>
          <span class="content"><?php print $fields['field_modelversion_dataset']->content; ?></span>
        </div>
        <?php endif; ?>
        <?php if (isset($fields['field_modelversion_sensitivity'])): ?>
        <div>
          <span class="label">Sensitivity Analysis:</span>
          <span class="content"><?php print $fields['field_modelversion_sensitivity']->content; ?></span>
        </div>
        <?php endif; ?>
        <?php if (isset($fields['field_modelversion_addfiles'])): ?>
        <div>
          <span class="label">Other Files:</span>
          <span class="content"><?php print $fields['field_modelversion_addfiles']->content; ?></span>
        </div>
        <?php endif; ?>
      </div>
      <!--googleon: all-->
      </div>
    </td>
  </tr>
</table>
<?php
$query = new EntityFieldQuery();
$query->entityCondition('entity_type','node')
      ->entityCondition('bundle','modelversion')
      ->propertyCondition('status', 1)
      ->fieldCondition('field_modelversion_model', 'nid', $modelnid, '=');
$result = $query->execute();

$nids = array_keys($result['node']);

if (count($nids) > 1): ?>
<div class="hrline" style="margin: 5px 0 0 0;"></div>
<div class="model-region5 versions-list">
  <div class="model-section-title">Available Model Versions</div>

  <table width="100%" id="striped" class="model-versions">
    <thead>
      <tr>
        <th class="field-modelversion-number">Version Number</th>
        <th class="field-modelversion-created">Submitted</th>
        <th class="field-body"></th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($nids as $index => $nid):
  $node = node_load($nid);

  $object = entity_metadata_wrapper('node', $node);
  $version_number = $object->field_modelversion_number->value();
  $body = field_get_items('node', $node, 'body');
?>
      <tr class="<?php print $index + 1; ?> <?php print (($index + 1) % 2 == 0 ? 'even' : 'odd');  ?>">
        <td <?php print 'onclick="DoNav(\'/model/'. $modelnid .'/version/'. $version_number .'/view\');"'; ?>class="field-modelversion-number"><?php print $version_number; ?></td>
        <td <?php print 'onclick="DoNav(\'/model/'. $modelnid .'/version/'. $version_number .'/view\');"'; ?>class="field-modelversion-created"><?php print date('m/j/Y', $node->created); ?></td>
        <td <?php print 'onclick="DoNav(\'/model/'. $modelnid .'/version/'. $version_number .'/view\');"'; ?>class="field-body"><?php print check_plain($body[0]['value']); ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
</div> <!-- div.model-nid -->
<div class="hrline" style="margin: 5px 0 0 0;"></div>
<div class="model-region model-region6">
  <div class="model-section-title">Community Comments</div>
  <div class="model-review-form">
<?php
  /**
   * Determine if current user has already posted a review for this model version, and generate the Review form if needed
   */
  $sql = "SELECT COUNT(comment.uid) AS comment_count FROM {comment} INNER JOIN {node} node_comment ON comment.nid = node_comment.nid "
         ."LEFT JOIN {field_data_field_modelversion_model} node_comment_modelversion_model ON node_comment.nid = node_comment_modelversion_model.entity_id AND (node_comment_modelversion_model.entity_type = 'node' AND node_comment_modelversion_model.deleted = '0') "
         ."INNER JOIN {node} modelversion_model ON node_comment_modelversion_model.field_modelversion_model_nid = modelversion_model.nid "
         ."LEFT JOIN {field_data_field_modelversion_number} modelversion_number ON node_comment.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
         ."WHERE (( (modelversion_model.nid = :modelnid ) AND (modelversion_number.field_modelversion_number_value = :versionnum) AND (comment.uid = :uid) )AND(( (comment.status <> '0') AND (node_comment.status = '1') )))";

  $comment_count = db_query($sql, array(':modelnid' => $modelnid, ':uid' => $user->uid, ':versionnum' => $fields['field_modelversion_number']->content))->fetchField();


  if ($comment_count == 0) {
    $version_node = load_modelversion($modelnid);
    $comment_form = modellibrary_static_comments($fields['field_modelversion_number']->content, false, $version_node);

    print drupal_render($comment_form);
  }
?>
  </div>

<?php
  /**
   * Count comments for the current (viewed) model version, and generate the HTML and comments if needed
   */
  $sql = "SELECT COUNT(comment.cid) AS comment_count FROM {comment} INNER JOIN {node} node_comment ON comment.nid = node_comment.nid "
         ."LEFT JOIN {field_data_field_modelversion_model} node_comment_modelversion_model ON node_comment.nid = node_comment_modelversion_model.entity_id AND (node_comment_modelversion_model.entity_type = 'node' AND node_comment_modelversion_model.deleted = '0') "
         ."INNER JOIN {node} modelversion_model ON node_comment_modelversion_model.field_modelversion_model_nid = modelversion_model.nid "
         ."LEFT JOIN {field_data_field_modelversion_number} modelversion_number ON node_comment.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
         ."WHERE (( (modelversion_model.nid = :modelnid ) AND (modelversion_number.field_modelversion_number_value = :versionnum) )AND(( (comment.status <> '0') AND (node_comment.status = '1') )))";

  $comment_count = db_query($sql, array(':modelnid' => $modelnid, ':versionnum' => $fields['field_modelversion_number']->content))->fetchField();
?>
<?php if ($comment_count > 0) : ?>
  <div class="model-review-group model-review-group-current">
    <h2 class="model-section-title">Current Version</h2>
<?php
// Render the Comments for current model version
$argument1 = $modelnid;
$argument2 = $fields['field_modelversion_number']->content;
print views_embed_view('model_comments', 'panel_pane_1', $argument1, $argument2);
?>
  </div>
<?php else : ?>
    <div class="">
      <p>
        No comments have been posted yet for this version. <?php ($user->uid > 0 ? print 'Click \'Add a comment\' above to add your comments on this model. ' : print 'You must be logged in to post a comment: <a href="/user">Log In</a>.') ?>
      </p>
    </div>
<?php endif; ?>
<?php
  /**
   * Count comments for the other model versions not being viewed, and generate the HTML and comments if needed
   */
  $sql = "SELECT COUNT(comment.cid) AS comment_count FROM {comment} INNER JOIN {node} node_comment ON comment.nid = node_comment.nid "
         ."LEFT JOIN {field_data_field_modelversion_model} node_comment_modelversion_model ON node_comment.nid = node_comment_modelversion_model.entity_id AND (node_comment_modelversion_model.entity_type = 'node' AND node_comment_modelversion_model.deleted = '0') "
         ."INNER JOIN {node} modelversion_model ON node_comment_modelversion_model.field_modelversion_model_nid = modelversion_model.nid "
         ."LEFT JOIN {field_data_field_modelversion_number} modelversion_number ON node_comment.nid = modelversion_number.entity_id AND (modelversion_number.entity_type = 'node' AND modelversion_number.deleted = '0') "
         ."WHERE (( (modelversion_model.nid = :modelnid ) AND (modelversion_number.field_modelversion_number_value <> :versionnum) )AND(( (comment.status <> '0') AND (node_comment.status = '1') )))";

  $comment_count = db_query($sql, array(':modelnid' => $modelnid, ':versionnum' => $fields['field_modelversion_number']->content))->fetchField();
?>
  <?php if ($comment_count > 0) : ?>
  <div class="model-review-group model-review-group-other">
    <h2 class="model-section-title">Other Versions</h2>
<?php
// Render the Comments for other model versions
$argument1 = $modelnid;
$argument2 = $fields['field_modelversion_number']->content;
print views_embed_view('model_comments', 'panel_pane_4', $argument1, $argument2);
?>
  </div>
  <?php endif; ?>
</div>
