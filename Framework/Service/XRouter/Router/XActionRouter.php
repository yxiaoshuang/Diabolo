<?php
namespace X\Service\XRouter\Router;
use X\Core\X;
use X\Service\XRouter\Service as XRouterService;
use X\Service\XRouter\Router\RouterInterface;
class XActionRouter implements RouterInterface {
    /** @var XRequestService $service */
    private $routerService = null;
    /** @var \X\Core\Module\Manager */
    private $moduleManager = null;
    /** @var string|null */
    private $fakeExt = null;
    /** @var string */
    private $mainModuleName = null;
    /** @var string */
    private $defaultAction = null;
    /** @var boolean */
    private $hideMainModuleName = false;
    
    /**
     * {@inheritDoc}
     * @see \X\Service\XRequest\RouterInterface::__construct()
     */
    public function __construct(XRouterService $service) {
        $this->routerService = $service;
        $this->fakeExt = $service->getConfiguration()->get('fakeExt', null);
        $this->mainModuleName = $service->getConfiguration()->get('mainModuleName','dionysos');
        $this->defaultAction = $service->getConfiguration()->get('defaultAction','index');
        $this->hideMainModuleName = $service->getConfiguration()->get('hideMainModuleName', false);
        $this->moduleManager = X::system()->getModuleManager();
    }

    /**
     * {@inheritDoc}
     * @see \X\Service\XRequest\RouterInterface::route()
     */
    public function route($url) {
        $urlInfo = parse_url($url);
        $params = array();
        if ( isset($urlInfo['query']) ) {
            parse_str($urlInfo['query'], $params); 
        }
        
        # 去除后缀
        $path = $urlInfo['path'];
        if ( null !== $this->fakeExt ) {
            $path = substr($path, 0, strrpos($path, '.'.$this->fakeExt));
        }
        
        if ( empty($path) ) {
            $path = array();
        } else {
            $path = explode('/', ltrim($path, '/'));
        }
        
        # 解析出参数和执行路径
        foreach ( $path as $index => $pathItem ) {
            if ( false !== strpos($pathItem, '-') ) {
                $pathItem = explode('-', $pathItem);
                $params[$pathItem[0]] = $pathItem[1];
                $path[$index] = $pathItem[0];
            }
        }
        
        $module = $this->mainModuleName;
        # 在启用隐藏主模块名的情况下，如果路径中的第一个元素是一个模块名，则指定模块，否则使用配置的模块名
        if ( $this->hideMainModuleName && !empty($path) && $this->moduleManager->has(ucfirst($path[0])) ) {
            $module = $path[0];
            unset($path[0]);
        }
        
        # 判断路径的最后一个元素，如果最后一个元素在参数列表中，则为'detail'操作，
        end($path);
        if ( !empty($path) && isset($params[current($path)]) ) {
            $path[] = 'detail';
        }
        reset($path);
        
        if ( empty($path) && isset($params[$module]) ) {
            $path[] = 'detail';
        }
        
        $fragment = '';
        if ( isset($urlInfo['fragment']) ) {
            $fragment = '#'.$urlInfo['fragment'];
        }
        
        if ( !empty($params) ) {
            $params = '&'.http_build_query($params);
        } else {
            $params = '';
        }
        
        if ( empty($path) ) {
            $path[] = $this->defaultAction;
        }
        $path = implode('/', $path);
        $url = "index.php?module={$module}&action={$path}{$params}{$fragment}";
        return $url;
    }

    /**
     * {@inheritDoc}
     * @see \X\Service\XRequest\RouterInterface::format()
     * @example index.php?module=food&action=detail&food=001 => /food-001
     * @example index.php?module=food&action=user/together/edit&food=001&together=002 => /food-001/user/together-002/edit
     * @example index.php?module=main&action=search&text=eeee => /search?text=eeee
     */
    public function format($url) {
        $urlInfo = parse_url($url);
        $path = array();
        
        $fragment = '';
        if ( isset($urlInfo['fragment']) ) {
            $fragment = '#'.$urlInfo['fragment'];
        }
        
        $query = '';
        if ( isset($urlInfo['query']) ) {
            parse_str($urlInfo['query'], $params);
            
            # 处理module
            $module = $params['module'];
            if ( $this->hideMainModuleName && $this->mainModuleName === $module ) {
                unset($params[$module]);
            } else if ( isset($params[$module]) ) {
                $path[] = $module.'-'.$params[$module];
                unset($params[$module]);
            } else {
                $path[] = $module;
            }
            unset($params['module']);
            
            # 处理action
            $action = explode('/', $params['action']);
            if ( 'detail' === $action[count($action)-1] ) {
                array_pop($action);
            }
            foreach ( $action as $actionPath ) {
                if ( isset($params[$actionPath]) ) {
                    $path[] = $actionPath.'-'.$params[$actionPath];
                    unset($params[$actionPath]);
                } else {
                    $path[] = $actionPath;
                }
            }
            unset($params['action']);
        }
        
        $query = '';
        if ( !empty($params) ) {
            $query = '?'.http_build_query($params);
        }
        
        $path = implode('/', $path);
        if ( null !== $this->fakeExt ) {
            $path .= '.'.$this->fakeExt;
        }
        return '/'.$path.$query.$fragment;
    }
    
    /** @var self */
    private static $instance = null;
    
    /**
     * 生成URL
     * @param unknown $url
     * @return unknown
     */
    public static function generate( $url ) {
        if ( null === self::$instance ) {
            self::$instance = new self(XRouterService::getService());
        }
        return self::$instance->format($url);
    }
    
    /**
     * @param unknown $module
     * @param unknown $action
     * @param array $params
     * @return \X\Module\Demo\unknown
     */
    public static function action($module,$action,$params=array()) {
        $url = "/index.php?module={$module}&action={$action}";
        
        $params = array_filter($params);
        if ( !empty($params) ) {
            $url .= '&'.http_build_query($params);
        }
        return self::generate($url);
    }
}