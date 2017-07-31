<?php
namespace Drupal\tawk_to\core;

use Symfony\Component\HttpFoundation\JsonResponse;

define('TAWK_TO_WIDGET_PID', 'tawk_to_widget_pid'); // page ID
define('TAWK_TO_WIDGET_WID', 'tawk_to_widget_wid'); // widget ID
define('TAWK_TO_WIDGET_OPTS', 'tawk_to_widget_options'); // options
define('TAWK_TO_WIDGET_UID', 'tawk_to_widget_uid'); // user ID


class TawktoGenerator
{
    public function widget()
    {
        // // Default settings.
        // $config = \Drupal::config('tawk_to.settings');
        // // Page title and source text.
        // $page_id = $config->get('tawk_to.page_id');
        // $widget_id = $config->get('tawk_to.widget_id');
        
        return $this->getWidget();
    }
    

    /**
     * Return widget details from the database.
     */
    public function getWidget()
    {
        $widgetVars = $this->getWidgetVars();
        extract($widgetVars);
        if (!$page_id || !$widget_id) {
            return '';
        }
        
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        if ($user) {
            $username = $user->get('name')->value;
            $usermail = $user->get('mail')->value;

            $apiString = '$_Tawk_API.visitor = {
                name  : "'.$username.'",
                email : "'.$usermail.'",
            };';
        }

        ob_start();
        ?><!--Start of Tawk.to Script-->
          <script type="text/javascript">
          var $_Tawk_API={},$_Tawk_LoadStart=new Date();
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

    public function settings()
    {
        // Default settings.
        $config = \Drupal::config('tawk_to.settings');
        // Page title and source text.
        $page_id = $config->get('tawk_to.page_id');
        $widget_id = $config->get('tawk_to.widget_id');
    }

    /**
     * Constructs url for configuration iframe.
     */
    public function getIframeUrl()
    {
      if (!$widget = $this->getWidgetVars()) {
        $widget = array(
                'page_id'   => '',
                'widget_id' => '',
            );
      }
      return $this->getBaseUrl() . '/generic/widgets?currentWidgetId=' . $widget['widget_id'] . '&currentPageId=' . $widget['page_id'];
    }

    /**
     * Base url for tawk.to application which serves iframe.
     */
    public function getBaseUrl() {
      return 'https://plugins.tawk.to';
    }

    public function getIframe()
    {
        $baseUrl = $this->getBaseUrl();
        $iframeUrl = $this->getIframeUrl();

        $vars = $this->getWidgetVars();
        extract($vars);

        // $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $sameUser = false;
        if (is_null($user_id) || \Drupal::currentUser()->id()==$user_id) {
            $sameUser = true;
        }

        $display_opts = $options;
        if (!is_null($display_opts)) {
            $display_opts = json_decode($display_opts);
        }
        ob_start();
        ?>
        <link href="https://plugins.tawk.to/public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
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
                        <div class="panel panel-default" id="fieldset_1">
                            <div class="form-group col-lg-12">
                                <div class="panel-heading"><strong>Visibility Settings</strong></div>
                            </div>
                            <br>
                            <div class="form-group col-lg-12">
                                <label for="always_display" class="col-lg-6 control-label">Always show Tawk.To widget on every page</label>
                                <div class="col-lg-6 control-label ">
                                    <?php
                                    $checked = true;
                                    if (!is_null($display_opts)) {
                                        if (!$display_opts->always_display) {
                                            $checked = false;
                                        }
                                    }
                                    ?>
                                    <input type="checkbox" class="col-lg-6" name="always_display" id="always_display" value="1" 
                                        <?php echo ($checked)?'checked':'';?> />
                                </div>
                            </div>

                            <div class="form-group col-lg-12">
                                <label for="hide_oncustom" class="col-lg-6 control-label">Except on pages:</label>
                                <div class="col-lg-6 control-label">
                                    <?php if (!empty($display_opts->hide_oncustom)) : ?>
                                        <?php $whitelist = json_decode($display_opts->hide_oncustom) ?>
                                        <textarea class="form-control hide_specific" name="hide_oncustom" 
                                            id="hide_oncustom" cols="30" rows="10"><?php foreach ($whitelist as $page) { echo $page."\r\n"; } ?></textarea>
                                    <?php else : ?>
                                        <textarea class="form-control hide_specific" name="hide_oncustom" id="hide_oncustom" cols="30" rows="10"></textarea>
                                    <?php endif; ?>
                                    <br>
                                    <p style="text-align: justify;">
                                    Add URLs to pages in which you would like to hide the widget. ( if "always show" is checked )<br>
                                    Put each URL in a new line.
                                    </p>
                                </div>
                            </div>

                            <div class="form-group col-lg-12">
                                <label for="show_onfrontpage" class="col-lg-6 control-label">Show on frontpage</label>
                                <div class="col-lg-6 control-label ">
                                    <?php
                                    $checked = false;
                                    if (!is_null($display_opts)) {
                                        if ($display_opts->show_onfrontpage) {
                                            $checked = true;
                                        }
                                    }
                                    ?>
                                    <input type="checkbox" class="col-lg-6 show_specific" name="show_onfrontpage" 
                                        id="show_onfrontpage" value="1" 
                                        <?php echo ($checked)?'checked':'';?> />
                                </div>
                            </div>
                            
                            <div class="form-group col-lg-12">
                                <label for="show_oncategory" class="col-lg-6 control-label">Show on category pages</label>
                                <div class="col-lg-6 control-label ">
                                    <?php
                                    $checked = false;
                                    if (!is_null($display_opts)) {
                                        if ($display_opts->show_oncategory) {
                                            $checked = true;
                                        }
                                    }
                                    ?>
                                    <input type="checkbox" class="col-lg-6 show_specific" name="show_oncategory" id="show_oncategory" value="1" 
                                        <?php echo ($checked)?'checked':'';?>  />
                                </div>
                            </div>
                            
                            <div class="form-group col-lg-12">
                                <label for="show_oncustom" class="col-lg-6 control-label">Show on pages:</label>
                                <div class="col-lg-6 control-label">
                                    <?php if (isset($display_opts->show_oncustom) && !empty($display_opts->show_oncustom)) : ?>
                                        <?php $whitelist = json_decode($display_opts->show_oncustom) ?>
                                        <textarea class="form-control show_specific" name="show_oncustom" id="show_oncustom" cols="30" 
                                            rows="10"><?php foreach ($whitelist as $page) { echo $page."\r\n"; } ?></textarea>
                                    <?php else : ?>
                                        <textarea class="form-control show_specific" name="show_oncustom" id="show_oncustom" cols="30" rows="10"></textarea>
                                    <?php endif; ?>
                                    <br>
                                    <p style="text-align: justify;">
                                    Add URLs to pages in which you would like to show the widget.<br>
                                    Put each URL in a new line.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-footer" style="position: relative; overflow: hidden; width: 100%; padding: 5px 0;">
                            <div id="optionsSuccessMessage" style="position:absolute;top:0;left;0;background-color: #dff0d8; color: #3c763d; border-color: #d6e9c6; font-weight: bold; display: none;" class="alert alert-success col-lg-5">Successfully set widget options to your site</div>
                            <label for="show_oncustom" class="col-lg-6 control-label"></label>
                            <div class="form-group">
                                <button type="submit" value="1" id="module_form_submit_btn" name="submitBlockCategories" class="btn btn-default pull-right"><i class="process-icon-save"></i> Save</button>    
                            </div>
                        </div>
                    </form>
                    
                </div>
                <div class="col-lg-4"></div>
            </div>
        </div>
        <script>
        var currentHost = window.location.protocol + "//" + window.location.host;
        var url = "<?php echo $iframeUrl; ?>&parentDomain=" + currentHost;

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

    public function getWidgetVars()
    {
        // return array(
        //         'page_id' => \Drupal::state()->get(TAWK_TO_WIDGET_PID),
        //         'widget_id' => \Drupal::state()->get(TAWK_TO_WIDGET_WID),
        //     );
        $config = \Drupal::service('config.factory')->getEditable('tawk_to.settings');
        return array(
                'page_id' => $config->get('tawk_to.page_id'),
                'widget_id' => $config->get('tawk_to.widget_id'),
                'user_id' => $config->get('tawk_to.user_id'),
                'options' => $config->get('tawk_to.options')
            );
    }

    public function setWidget($page, $widget)
    {
        $page = trim($page);
        $widget = trim($widget);

        $options = array('success' => false);
        if (!$page || !$widget) {
            return new JsonResponse($options);
        }

        if (preg_match('/^[0-9A-Fa-f]{24}$/', $page) !== 1 
            || preg_match('/^[a-z0-9]{1,50}$/i', $widget) !== 1) {
            return new JsonResponse($options);
        }

        // $config = $this->config('tawk_to.settings');
        $config = \Drupal::service('config.factory')->getEditable('tawk_to.settings');
        $config->set('tawk_to.page_id', $page);
        $config->set('tawk_to.widget_id', $widget);
        $config->set('tawk_to.user_id', \Drupal::currentUser()->id());
        // $config->set('tawk_to.options', $options);
        // 
        $config->save();

        // \Drupal::state()->set(TAWK_TO_WIDGET_PID, $page);
        // \Drupal::state()->set(TAWK_TO_WIDGET_WID, $widget);

        $options = array('success' => true);
        return new JsonResponse($options);
    }

    public function removeWidget()
    {
        $config = \Drupal::service('config.factory')->getEditable('tawk_to.settings');
        $config->set('tawk_to.page_id', 0);
        $config->set('tawk_to.widget_id', 0);
        $config->set('tawk_to.user_id', 0);
        // $config->set('tawk_to.options', 0);
        
        $config->save();

        $options = array('success' => true);
        return new JsonResponse($options);
    }

    public function setOptions($options)
    {
        if (!$options) {
            return new JsonResponse(array('success' => false));
        }
        $jsonOpts = array(
                'always_display' => false,
                'hide_oncustom' => false,
                'show_onfrontpage' => false,
                'show_oncategory' => false,
                'show_oncustom' => array(),
            );

        $options = explode('&', $options);
        foreach ($options as $post) {
            list($column, $value) = explode('=', $post);
            switch ($column) {
                case 'hide_oncustom':
                case 'show_oncustom':
                    // replace newlines and returns with comma, and convert to array for saving
                    $value = urldecode($value);
                    $value = str_ireplace(["\r\n", "\r", "\n"], ',', $value);
                    $value = explode(",", $value);
                    $value = (empty($value)||!$value)?array():$value;
                    $jsonOpts[$column] = json_encode($value);
                    break;
                
                case 'show_onfrontpage':
                case 'show_oncategory':
                case 'always_display':
                // default:
                    $jsonOpts[$column] = ($value==1)?true:false;
                    break;
            }
        }
        $config = \Drupal::service('config.factory')->getEditable('tawk_to.settings');
        $config->set('tawk_to.options', json_encode($jsonOpts));
        $config->save();

        return new JsonResponse(array('success' => true));
    }
}
