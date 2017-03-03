<?php namespace Seals\Library;
/**
 * @author yuyi
 * @created 2016/11/22 6:22
 * @email 297341015@qq.com
 *
 * 进程调度接口
 */
interface Dispatch{
    //返回目标工作进程的名称 可以自定义调度算法
    public function get();
}