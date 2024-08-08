<?php

namespace Drupal\tawk_to\core;

require_once drupal_get_path('module', 'tawk_to') . '/vendor/autoload.php';

use Drupal\Core\Cache\Cache;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tawk\Modules\UrlPatternMatcher;

// Page ID.
define('TAWK_TO_WIDGET_PID', 'tawk_to_widget_pid');
// Widget ID.
define('TAWK_TO_WIDGET_WID', 'tawk_to_widget_wid');
// Options.
define('TAWK_TO_WIDGET_OPTS', 'tawk_to_widget_options');
// User ID.
define('TAWK_TO_WIDGET_UID', 'tawk_to_widget_uid');

/**
 * Provides frontend for chat widget and admin settings.
 */
class TawktoGenerator {

  /**
   * Calls getWidget()
   */
  public function widget() {
    return $this->getWidget();
  }

  /**
   * Return widget details from the database.
   */
  public function getWidget() {
    $widgetVars = $this->getWidgetVars();
    extract($widgetVars);
    if (!$page_id || !$widget_id) {
      return '';
    }

    if (!$this->shouldDisplayWidget($options)) {
      return '';
    }

    $display_opts = $options;
    // Default value.
    $enable_visitor_recognition = TRUE;
    if (!is_null($display_opts)) {
      $display_opts = json_decode($display_opts);

      if (!is_null($display_opts->enable_visitor_recognition)) {
        $enable_visitor_recognition = $display_opts->enable_visitor_recognition;
      }
    }

    if ($enable_visitor_recognition) {
      $user = User::load(\Drupal::currentUser()->id());
      if ($user) {
        $username = $user->get('name')->value;
        $usermail = $user->get('mail')->value;

        $apiString = 'Tawk_API.visitor = {
                    name  : "' . $username . '",
                    email : "' . $usermail . '",
                };';
      }
    }

    ob_start();
    ?><!--Start of Tawk.to Script-->
          <script type="text/javascript">
          var Tawk_API=Tawk_API||{},Tawk_LoadStart=new Date();
          (function(){
          var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
          s1.async=true;
          s1.src="https://embed.tawk.to/<?php echo $page_id; ?>/<?php echo $widget_id; ?>";
          s1.charset="UTF-8";
          s1.setAttribute("crossorigin","*");
          s0.parentNode.insertBefore(s1,s0);
          })();
          <?php echo $apiString; ?>
          </script>
          <!--End of Tawk.to Script--><?php
          $output = ob_get_contents();
          ob_end_clean();
          return $output;
  }

  /**
   * Check widget visibility based on set options.
   */
  private function shouldDisplayWidget($options = NULL) {
    if (!$options || is_null($options)) {
      // Since always_show's default value is true.
      return TRUE;
    }

    global $base_url;
    $options = json_decode($options);
    $show = FALSE;

    // Prepare visibility.
    $currentUrl = $base_url . $_SERVER["REQUEST_URI"];
    if ($options->always_display == FALSE) {
      $show_pages = json_decode($options->show_oncustom);

      if (UrlPatternMatcher::match($currentUrl, $show_pages)) {
        $show = TRUE;
      }

      // Check if category/taxonomy page
      // taxonomy page.
      if ("taxonomy_term" == strtolower(\Drupal::request()->attributes->get('view_id'))) {
        if (FALSE != $options->show_oncategory) {
          $show = TRUE;
        }
      }

      // Check if frontpage.
      if (\Drupal::service('path.matcher')->isFrontPage()) {
        if (FALSE != $options->show_onfrontpage) {
          $show = TRUE;
        }
      }
    }
    else {
      $hide_pages = json_decode($options->hide_oncustom);
      $show = TRUE;

      $currentUrl = (string) $currentUrl;
      if (UrlPatternMatcher::match($currentUrl, $hide_pages)) {
        $show = FALSE;
      }
    }

    return $show;
  }

  /**
   * Get current settings.
   */
  public function settings() {
    // Default settings.
    $config = \Drupal::config('tawk_to.settings');
    // Page title and source text.
    $page_id = $config->get('tawk_to.page_id');
    $widget_id = $config->get('tawk_to.widget_id');
  }

  /**
   * Constructs url for configuration iframe.
   */
  public function getIframeUrl() {
    $widget = $this->getWidgetVars();
    extract($widget);
    if (!$page_id || !$widget_id) {
      $widget = [
        'page_id'   => '',
        'widget_id' => '',
      ];
    }
    return $this->getBaseUrl() . '/generic/widgets?currentWidgetId=' . $widget['widget_id'] . '&currentPageId=' . $widget['page_id'];
  }

  /**
   * Base url for tawk.to application which serves iframe.
   */
  public function getBaseUrl() {
    return 'https://plugins.tawk.to';
  }

  /**
   * Get admin settings template.
   */
  public function getIframe() {
    $baseUrl = $this->getBaseUrl();
    $iframeUrl = $this->getIframeUrl();

    $vars = $this->getWidgetVars();
    extract($vars);

    $sameUser = FALSE;
    if (is_null($user_id) || \Drupal::currentUser()->id() == $user_id) {
      $sameUser = TRUE;
    }

    $display_opts = $options;
    if (!is_null($display_opts)) {
      $display_opts = json_decode($display_opts);
    }
    ob_start();
    ?>
        <link href="https://plugins.tawk.to/public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <style>
            #module_form .checkbox {
                display: inline-block;
                min-height: 20px;
            }

            @media only screen and (min-width: 1200px) {
                #module_form .checkbox {
                    display: block;
                }
            }

            /* Tooltip */
            .tooltip {
            position: relative;
            display: inline;
            color: #03a84e;
            }

            .tooltip .tooltiptext {
            visibility: hidden;
            background-color: #545454;
            color: #fff;
            text-align: center;
            padding: 0.5rem;
            max-width: 300px;
            border-radius: 0.5rem;
            line-height: 0.9;

            /* Position the tooltip text - see examples below! */
            position: absolute;
            z-index: 1000;
            top: 12px;
            }

            .tooltip .tooltiptext::before {
            content: "";
            display: block;
            width: 0;
            height: 0;
            position: absolute;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-bottom: 5px solid #545454;
            top: -5px;
            left: 5px;
            }

            .tooltip:hover .tooltiptext {
            visibility: visible;
            }
        </style>
        <?php if (!$sameUser) : ?>
            <div id="widget_already_set" style="width: 100%; float: left; color: #3c763d; border-color: #d6e9c6; font-weight: bold; margin: 20px 0;" class="alert alert-warning">Notice: Widget already set by other user</div>
        <?php endif; ?>
        <iframe id="tawkIframe" src="" style="min-height: 275px; width : 100%; border: none; margin: 5px 0; padding: 10px; background: #FFF;"></iframe>

        <input type="hidden" class="hidden" name="page_id" value="<?php echo $widget['page_id']?>">
        <input type="hidden" class="hidden" name="widget_id" value="<?php echo $widget['widget_id']?>">
        <div id="content" class="bootstrap" style="margin-top: -20px;">
            <hr>
            <div class="row">
                <div class="col-lg-8">
                    <form id="module_form" class="form-horizontal" action="" method="post">
                        <div id="fieldset_1">
                            <div class="panel form-group col-xs-12">
                                <div class="panel-heading"><strong>Visibility Settings</strong></div>
                            </div>
                            <div class="form-group col-xs-12">
                                <label for="always_display" class="col-xs-6 control-label">Always show Tawk.To widget on every page</label>
                                <div class="col-xs-6 control-label ">
                                    <?php
                                    $checked = TRUE;
                                    if (!is_null($display_opts)) {
                                      if (!$display_opts->always_display) {
                                        $checked = FALSE;
                                      }
                                    }
                                    ?>
                                    <input type="checkbox" class="checkbox" name="always_display" id="always_display" value="1"
                                        <?php echo ($checked) ? 'checked' : '';?> />
                                </div>
                            </div>

                            <div class="form-group col-xs-12">
                                <label for="hide_oncustom" class="col-xs-6 control-label">Except on pages:</label>
                                <div class="col-xs-6 control-label">
                                    <?php if (!empty($display_opts->hide_oncustom)) : ?>
                                        <?php $whitelist = json_decode($display_opts->hide_oncustom) ?>
                                        <textarea class="form-control hide_specific" name="hide_oncustom"
                                            id="hide_oncustom" cols="30" rows="10"><?php
                                            foreach ($whitelist as $page) {
                                              echo $page . "\r\n";
                                            } ?></textarea>
                                    <?php else : ?>
                                        <textarea class="form-control hide_specific" name="hide_oncustom" id="hide_oncustom" cols="30" rows="10"></textarea>
                                    <?php endif; ?>
                                    <br>
                                    <div style="text-align: justify;">
                                        Add URLs/paths to pages in which you would like to hide the widget.<br>
                                        Put each URL/path in a new line. Paths should have a leading '/'.
                                        <br>
                                        <div class="tooltip">
                                            Examples of accepted path patterns
                                            <ul class="tooltiptext">
                                                <li>*</li>
                                                <li>*/to/somewhere</li>
                                                <li>/*/to/somewhere</li>
                                                <li>/path/*/somewhere</li>
                                                <li>/path/*/lead/*/somewhere</li>
                                                <li>/path/*/*/somewhere</li>
                                                <li>/path/to/*</li>
                                                <li>/path/to/*/</li>
                                                <li>*/to/*/page</li>
                                                <li>/*/to/*/page</li>
                                                <li>/path/*/other/*</li>
                                                <li>/path/*/other/*/</li>
                                                <li>http://www.example.com/</li>
                                                <li>http://www.example.com/*</li>
                                                <li>http://www.example.com/*/to/somewhere</li>
                                                <li>http://www.example.com/path/*/somewhere</li>
                                                <li>http://www.example.com/path/*/lead/*/somewhere</li>
                                                <li>http://www.example.com/path/*/*/somewhere</li>
                                                <li>http://www.example.com/path/to/*</li>
                                                <li>http://www.example.com/path/to/*/</li>
                                                <li>http://www.example.com/*/to/*/page</li>
                                                <li>http://www.example.com/path/*/other/*</li>
                                                <li>http://www.example.com/path/*/other/*/</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-xs-12">
                                <label for="show_onfrontpage" class="col-xs-6 control-label">Show on frontpage</label>
                                <div class="col-xs-6 control-label ">
                                    <?php
                                    $checked = FALSE;
                                    if (!is_null($display_opts)) {
                                      if ($display_opts->show_onfrontpage) {
                                        $checked = TRUE;
                                      }
                                    }
                                    ?>
                                    <input type="checkbox" class="checkbox show_specific" name="show_onfrontpage"
                                        id="show_onfrontpage" value="1"
                                        <?php echo ($checked) ? 'checked' : '';?> />
                                </div>
                            </div>

                            <div class="form-group col-xs-12">
                                <label for="show_oncategory" class="col-xs-6 control-label">Show on category pages</label>
                                <div class="col-xs-6 control-label ">
                                    <?php
                                    $checked = FALSE;
                                    if (!is_null($display_opts)) {
                                      if ($display_opts->show_oncategory) {
                                        $checked = TRUE;
                                      }
                                    }
                                    ?>
                                    <input type="checkbox" class="checkbox show_specific" name="show_oncategory" id="show_oncategory" value="1"
                                        <?php echo ($checked) ? 'checked' : '';?>  />
                                </div>
                            </div>

                            <div class="form-group col-xs-12">
                                <label for="show_oncustom" class="col-xs-6 control-label">Show on pages:</label>
                                <div class="col-xs-6 control-label">
                                    <?php if (isset($display_opts->show_oncustom) && !empty($display_opts->show_oncustom)) : ?>
                                        <?php $whitelist = json_decode($display_opts->show_oncustom) ?>
                                        <textarea class="form-control show_specific" name="show_oncustom" id="show_oncustom" cols="30"
                                            rows="10"><?php
                                            foreach ($whitelist as $page) {
                                              echo $page . "\r\n";
                                            } ?></textarea>
                                    <?php else : ?>
                                        <textarea class="form-control show_specific" name="show_oncustom" id="show_oncustom" cols="30" rows="10"></textarea>
                                    <?php endif; ?>
                                    <br>
                                    <div style="text-align: justify;">
                                        Add URLs/paths to pages in which you would like to show the widget.<br>
                                        Put each URL/path in a new line. Paths should have a leading '/'.
                                        <br>
                                        <div class="tooltip">
                                            Examples of accepted path patterns
                                            <ul class="tooltiptext">
                                                <li>*</li>
                                                <li>*/to/somewhere</li>
                                                <li>/*/to/somewhere</li>
                                                <li>/path/*/somewhere</li>
                                                <li>/path/*/lead/*/somewhere</li>
                                                <li>/path/*/*/somewhere</li>
                                                <li>/path/to/*</li>
                                                <li>/path/to/*/</li>
                                                <li>*/to/*/page</li>
                                                <li>/*/to/*/page</li>
                                                <li>/path/*/other/*</li>
                                                <li>/path/*/other/*/</li>
                                                <li>http://www.example.com/</li>
                                                <li>http://www.example.com/*</li>
                                                <li>http://www.example.com/*/to/somewhere</li>
                                                <li>http://www.example.com/path/*/somewhere</li>
                                                <li>http://www.example.com/path/*/lead/*/somewhere</li>
                                                <li>http://www.example.com/path/*/*/somewhere</li>
                                                <li>http://www.example.com/path/to/*</li>
                                                <li>http://www.example.com/path/to/*/</li>
                                                <li>http://www.example.com/*/to/*/page</li>
                                                <li>http://www.example.com/path/*/other/*</li>
                                                <li>http://www.example.com/path/*/other/*/</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="fieldset_2">
                            <div class="panel form-group col-xs-12">
                                <div class="panel-heading"><strong>Privacy Options</strong></div>
                            </div>
                            <div class="form-group col-xs-12">
                                <label for="enable_visitor_recognition" class="col-xs-6 control-label">Enable Visitor Recognition</label>
                                <div class="col-xs-6 control-label">
                                    <?php
                                    $checked = 'checked';
                                    if (!is_null($display_opts) && !$display_opts->enable_visitor_recognition) {
                                      $checked = '';
                                    }
                                    ?>
                                    <input type="checkbox" class="checkbox" name="enable_visitor_recognition" id="enable_visitor_recognition" value="1"
                                        <?php echo $checked ?> />
                                </div>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <div class="col-lg-6 col-xs-12" style="text-align: right; margin-bottom: 10px;">
                                <button type="submit" value="1" id="module_form_submit_btn" name="submitBlockCategories" class="btn btn-default pull-right"><i class="process-icon-save"></i> Save</button>
                            </div>
                            <div class="form-group col-lg-6 col-xs-12" style="min-height: 60px;">
                                <div id="optionsSuccessMessage" style="background-color: #dff0d8; color: #3c763d; border-color: #d6e9c6; font-weight: bold; display: none;" class="alert alert-success col-lg-12">Successfully set widget options to your site</div>
                            </div>
                        </div>
                    </form>

                </div>
                <div class="col-lg-4"></div>
            </div>
        </div>
        <script>
        var currentHost = window.location.protocol + "//" + window.location.host;
        var url = "<?php echo $iframeUrl; ?>&pltf=drupal&pltfv=8&parentDomain=" + currentHost;

        jQuery("#tawkIframe").attr("src", url);

        var iframe = jQuery("#tawk_widget_customization")[0];

        window.addEventListener("message", function(e) {
            if(e.origin === "<?php echo $baseUrl; ?>") {
                if(e.data.action === "setWidget") {
                    setWidget(e);
                }
                if(e.data.action === "removeWidget") {
                    removeWidget(e);
                }
                if(e.data.action === 'reloadHeight') {
                    reloadIframeHeight(e.data.height);
                }
            }
        });

        function setWidget(e) {
            jQuery.post("<?php echo base_path(); ?>admin/config/tawk_to/set_widget", {
                pid : e.data.pageId,
                wid : e.data.widgetId
            }, function(r) {
                if(r.success) {
                    $('#widget_already_set').hide();
                    e.source.postMessage({action: "setDone"}, "<?php echo $baseUrl; ?>");
                } else {
                    e.source.postMessage({action: "setFail"}, "<?php echo $baseUrl; ?>");
                }
            });
        }

        function removeWidget(e) {
            jQuery.post("<?php echo base_path(); ?>admin/config/tawk_to/remove_widget", function(r) {
            if(r.success) {
                $('#widget_already_set').hide();
                e.source.postMessage({action: "removeDone"}, "<?php echo $baseUrl; ?>");
            } else {
                e.source.postMessage({action: "removeFail"}, "<?php echo $baseUrl; ?>");
            }
            });
        }

        function reloadIframeHeight(height) {
            if (!height) {
                return;
            }

            var iframe = jQuery('#tawkIframe');
            if (height === iframe.height()) {
                return;
            }

            iframe.height(height);
        }

        jQuery(document).ready(function() {
            if (jQuery("#always_display").prop("checked")){
                jQuery('.show_specific').prop('disabled', true);
            } else {
                jQuery('.hide_specific').prop('disabled', true);
            }

            jQuery("#always_display").change(function() {
                if(this.checked){
                    jQuery('.hide_specific').prop('disabled', false);
                    jQuery('.show_specific').prop('disabled', true);
                }else{
                    jQuery('.hide_specific').prop('disabled', true);
                    jQuery('.show_specific').prop('disabled', false);
                }
            });

            // process the form
            jQuery('#module_form').submit(function(event) {
                $path = "<?php echo base_path(); ?>admin/config/tawk_to/set_options";
                jQuery.post($path, {
                    action     : 'set_visibility',
                    ajax       : true,
                    page_id    : jQuery('input[name="page_id"]').val(),
                    widget_id  : jQuery('input[name="widget_id"]').val(),
                    options    : jQuery(this).serialize()
                }, function(r) {
                    if(r.success) {
                        $('#optionsSuccessMessage').toggle().delay(3000).fadeOut();
                    }
                });

                // stop the form from submitting the normal way and refreshing the page
                event.preventDefault();
            });
        });
        </script>
        <?php
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
  }

  /**
   * Get current widget configuration.
   */
  public function getWidgetVars() {
    $config = \Drupal::service('config.factory')->getEditable('tawk_to.settings');
    return [
      'page_id' => $config->get('tawk_to.page_id'),
      'widget_id' => $config->get('tawk_to.widget_id'),
      'user_id' => $config->get('tawk_to.user_id'),
      'options' => $config->get('tawk_to.options'),
    ];
  }

  /**
   * Set widget configuration.
   */
  public function setWidget($page, $widget) {
    $page = trim($page);
    $widget = trim($widget);

    $options = ['success' => FALSE];
    if (!$page || !$widget) {
      return new JsonResponse($options);
    }

    if (preg_match('/^[0-9A-Fa-f]{24}$/', $page) !== 1
          || preg_match('/^[a-z0-9]{1,50}$/i', $widget) !== 1) {
      return new JsonResponse($options);
    }

    $config = \Drupal::service('config.factory')->getEditable('tawk_to.settings');
    $config->set('tawk_to.page_id', $page);
    $config->set('tawk_to.widget_id', $widget);
    $config->set('tawk_to.user_id', \Drupal::currentUser()->id());
    $config->save();

    Cache::invalidateTags(['tawk_widget']);

    $options = ['success' => TRUE];
    return new JsonResponse($options);
  }

  /**
   * Remove widget configuration.
   */
  public function removeWidget() {
    $config = \Drupal::service('config.factory')->getEditable('tawk_to.settings');
    $config->set('tawk_to.page_id', 0);
    $config->set('tawk_to.widget_id', 0);
    $config->set('tawk_to.user_id', NULL);

    $config->save();

    Cache::invalidateTags(['tawk_widget']);

    $options = ['success' => TRUE];
    return new JsonResponse($options);
  }

  /**
   * Sets additional options for widget.
   */
  public function setOptions($options) {
    if (!$options) {
      return new JsonResponse(['success' => FALSE]);
    }
    $jsonOpts = [
      'always_display' => FALSE,
      'hide_oncustom' => FALSE,
      'show_onfrontpage' => FALSE,
      'show_oncategory' => FALSE,
      'show_oncustom' => [],
      'enable_visitor_recognition' => FALSE,
    ];

    $options = explode('&', $options);
    foreach ($options as $post) {
      [$column, $value] = explode('=', $post);
      switch ($column) {
        case 'hide_oncustom':
        case 'show_oncustom':
          // Split by newlines, then remove empty lines.
          $value = urldecode($value);
          $value = str_ireplace("\r", "\n", $value);
          $value = explode("\n", $value);
          $non_empty_values = [];
          foreach ($value as $str) {
            $trimmed = trim($str);
            if ($trimmed !== '') {
              $non_empty_values[] = $trimmed;
            }
          }
          $jsonOpts[$column] = json_encode($non_empty_values);
          break;

        case 'show_onfrontpage':
        case 'show_oncategory':
        case 'always_display':
        case 'enable_visitor_recognition':
          $jsonOpts[$column] = $value == 1;
          break;
      }
    }
    $config = \Drupal::service('config.factory')->getEditable('tawk_to.settings');
    $config->set('tawk_to.options', json_encode($jsonOpts));
    $config->save();

    Cache::invalidateTags(['tawk_widget']);

    return new JsonResponse(['success' => TRUE]);
  }

}
