<?php
namespace Drupal\tawk_to\Controller;

use Drupal\tawk_to\core\TawktoGenerator;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;


class TawktoController extends ControllerBase
{
    private $generator;
    public function __construct(TawktoGenerator $generator, LoggerChannelFactory $loggerFactory)
    {
        $this->generator = $generator;
        $this->loggerFactory = $loggerFactory;
    }

    public function admin()
    {   
        $widget = $this->generator->widget();
        $this->loggerFactory->get('default')->debug($widget);

        $keyValueSvc = $this->keyValue('tawk_to');
        // $keyValueSvc->set('widget', '<script>the widget is this</script>');
        $widget = $keyValueSvc->get('widget');

        return new Response($widget);
    }

    public function widget()
    {
        $widget = $this->generator->widget();
        // $content = $this->generator->getIframe();
        // $this->loggerFactory->get('default')
        //     ->debug($content);
        return new Response($widget);
    }

    static public function create(ContainerInterface $container)
    {
        $generator = $container->get('tawk_to.chat_generator');
        $logger = $container->get('logger.factory');

        return new static($generator, $logger);
    }

    public function settings()
    {
        // return $this->generator->settings();
        // $build = array(
        //   '#type' => 'markup',
        //   '#markup' => t('Hello World!'),
        // );
        // return $build;
        // return $this->generator->getIframe();
        // return new Response($this->generator->getIframe());

        $build = array(
                '#type' => 'inline_template',
                '#template' => $this->generator->getIframe()
            );
        return $build;
    }

    public function set_widget()
    {
        if (!empty($_REQUEST) && 'POST' == $_SERVER["REQUEST_METHOD"]) {
            foreach ($_REQUEST as $key => $value) {
                if (stripos($key, 'SESS')!== false) {
                    if ($value == \Drupal::service('session')->getId()) {
                        return $this->_set($_REQUEST);
                    }
                }
            }
        }
    }

    private function _set($params)
    {
        $pid = '';
        $wid = '';
        extract($params);
        return $this->generator->setWidget($pid, $wid);
    }

    public function remove_widget()
    {
        if (!empty($_REQUEST) && 'POST' == $_SERVER["REQUEST_METHOD"]) {
            foreach ($_REQUEST as $key => $value) {
                if (stripos($key, 'SESS')!== false) {
                    if ($value == \Drupal::service('session')->getId()) {
                        return $this->_remove();
                    }
                }
            }
        }
    }

    public function set_options()
    {
        if (!empty($_REQUEST['options']) && 'POST' == $_SERVER["REQUEST_METHOD"]) {
            $options = trim($_REQUEST['options']);
            return $this->generator->setOptions($options);
        }
    }


    private function _remove()
    {
        return $this->generator->removeWidget();
    }
}
