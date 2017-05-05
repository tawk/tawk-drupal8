<?php
namespace Drupal\tawk_to\core;

use Symfony\Component\HttpFoundation\JsonResponse;

define('TAWK_TO_WIDGET_PID', 'tawk_to_widget_pid'); // page ID
define('TAWK_TO_WIDGET_WID', 'tawk_to_widget_wid'); // widget ID

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
        ob_start();
        ?>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <iframe id="tawkIframe" src="" style="min-height: 400px; width : 100%; border: none"></iframe>
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
                    e.source.postMessage({action: "setDone"}, "<?php echo $baseUrl; ?>");
                } else {
                    e.source.postMessage({action: "setFail"}, "<?php echo $baseUrl; ?>");
                }
            });
        }

        function removeWidget(e) {
            jQuery.post("<?php echo base_path(); ?>admin/config/tawk_to/remove_widget", function(r) {
            if(r.success) {
                e.source.postMessage({action: "removeDone"}, "<?php echo $baseUrl; ?>");
            } else {
                e.source.postMessage({action: "removeFail"}, "<?php echo $baseUrl; ?>");
            }
            });
        }
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
        $config->save();

        $options = array('success' => true);
        return new JsonResponse($options);
    }
}
