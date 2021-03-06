<?php

namespace Illuminate\View\Concerns;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View as ViewContract;

trait ManagesEvents
{
    /**
     * Register a view creator event.
     *
     * 注册一个视图创建器事件
     *
     * @param  array|string     $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function creator($views, $callback)
    {
        $creators = [];

        foreach ((array) $views as $view) {
            //              为给定的视图添加事件
            $creators[] = $this->addViewEvent($view, $callback, 'creating: ');
        }

        return $creators;
    }

    /**
     * Register multiple view composers via an array.
     *
     * 通过数组来注册多个视图的作曲家
     *
     * @param  array  $composers
     * @return array
     */
    public function composers(array $composers)
    {
        $registered = [];

        foreach ($composers as $callback => $views) {
            //                                     注册一个视图composer事件
            $registered = array_merge($registered, $this->composer($views, $callback));
        }

        return $registered;
    }

    /**
     * Register a view composer event.
     *
     * 注册一个视图composer事件
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function composer($views, $callback)
    {
        $composers = [];

        foreach ((array) $views as $view) {
            //                为给定的视图添加事件
            $composers[] = $this->addViewEvent($view, $callback, 'composing: ');
        }

        return $composers;
    }

    /**
     * Add an event for a given view.
     *
     * 为给定的视图添加事件
     *
     * @param  string  $view
     * @param  \Closure|string  $callback
     * @param  string  $prefix
     * @return \Closure|null
     */
    protected function addViewEvent($view, $callback, $prefix = 'composing: ')
    {
        $view = $this->normalizeName($view);//正常的视图名称

        if ($callback instanceof Closure) {
            //向事件调度程序添加侦听器
            $this->addEventListener($prefix.$view, $callback);

            return $callback;
        } elseif (is_string($callback)) {
            //注册一个基于类的视图composer
            return $this->addClassEvent($view, $callback, $prefix);
        }
    }

    /**
     * Register a class based view composer.
     *
     * 注册一个基于类的视图composer
     *
     * @param  string    $view
     * @param  string    $class
     * @param  string    $prefix
     * @return \Closure
     */
    protected function addClassEvent($view, $class, $prefix)
    {
        $name = $prefix.$view;

        // When registering a class based view "composer", we will simply resolve the
        // classes from the application IoC container then call the compose method
        // on the instance. This allows for convenient, testable view composers.
        //
        // 在注册一个基于类的视图“composer”时，我们将简单地从应用程序IoC容器中解析类，然后在实例上调用组合方法。这允许方便、可测试的视图composers
        //
        //                 构建基于类的容器回调闭包
        $callback = $this->buildClassEventCallback(
            $class, $prefix
        );

        //向事件调度程序添加侦听器
        $this->addEventListener($name, $callback);

        return $callback;
    }

    /**
     * Build a class based container callback Closure.
     *
     * 构建基于类的容器回调闭包
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return \Closure
     */
    protected function buildClassEventCallback($class, $prefix)
    {
        //                                    解析一个基于类的composer名
        list($class, $method) = $this->parseClassEvent($class, $prefix);

        // Once we have the class and method name, we can build the Closure to resolve
        // the instance out of the IoC container and call the method on it with the
        // given arguments that are passed to the Closure as the composer's data.
        //
        // 一旦我们有了类和方法名，我们就可以构建闭包，以解析IoC容器中的实例，并通过传递给闭包的参数来调用该方法，并将其作为编写器的数据传递给闭包
        //
        return function () use ($class, $method) {
            return call_user_func_array(
                [$this->container->make($class), $method], func_get_args()
            );
        };
    }

    /**
     * Parse a class based composer name.
     *
     * 解析一个基于类的composer名
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return array
     */
    protected function parseClassEvent($class, $prefix)
    {
        //解析 类@方法 类型回调到类和方法           根据给定的前缀确定类事件方法
        return Str::parseCallback($class, $this->classEventMethodForPrefix($prefix));
    }

    /**
     * Determine the class event method based on the given prefix.
     *
     * 根据给定的前缀确定类事件方法
     *
     * @param  string  $prefix
     * @return string
     */
    protected function classEventMethodForPrefix($prefix)
    {
        //确定一个给定的字符串包含另一个字符串
        return Str::contains($prefix, 'composing') ? 'compose' : 'create';
    }

    /**
     * Add a listener to the event dispatcher.
     *
     * 向事件调度程序添加侦听器
     *
     * @param  string    $name
     * @param  \Closure  $callback
     * @return void
     */
    protected function addEventListener($name, $callback)
    {
        //确定一个给定的字符串包含另一个字符串
        if (Str::contains($name, '*')) {
            $callback = function ($name, array $data) use ($callback) {
                return $callback($data[0]);
            };
        }
        //用分配器注册事件监听器
        $this->events->listen($name, $callback);
    }

    /**
     * Call the composer for a given view.
     *
     * 为给定的视图调用composer
     *
     * @param  \Illuminate\Contracts\View\View  $view
     * @return void
     */
    public function callComposer(ViewContract $view)
    {
        //触发事件并调用监听器
        $this->events->fire('composing: '.$view->name(), [$view]);
    }

    /**
     * Call the creator for a given view.
     *
     * 为给定的视图调用创建者
     *
     * @param  \Illuminate\Contracts\View\View  $view
     * @return void
     */
    public function callCreator(ViewContract $view)
    {
        //触发事件并调用监听器
        $this->events->fire('creating: '.$view->name(), [$view]);
    }
}
