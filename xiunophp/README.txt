-------------------------- English --------------------------------

XiunoPHP 4 is not a framework in the sense that it does not require you to organize your code, how to inherit the Control Base, Model Base, so it won't be like any other frame "box" to live in you. It's just adding some of the initial variables, and the global function. If you want to say that it is the framework, it can be said that it is a functional framework.

In the development of ideas, as possible, do not use of OO, most of the functions of the package, is conducive to the HHVM compiler / opcode cache, perfect support PHP7, and promote the following principles:
1. don't include variables
2. Do not use eval(), do not use the regular expression 'e' modifier
3 don't use autoload
4 don't use $$var multiple variables
5 don't use the PHP advanced feature __set __get __call namespace etc.
6 as far as possible the use of function package, through the prefix distinction module.

-------------------------- 中文 --------------------------------

XiunoPHP 4.0 严格意义上它不是一个框架，它并没有要求你如何组织代码，如何继承 Base Control, Base Model，所以，它不会像其他框架一样“框”住你。
它只是在对 PHP 进行了一些增强，增加了一些初始变量，和全局函数而已。

在开发理念上，尽可能少的采用 OO，大部分函数式封装，有利于 HHVM 编译 / opcode 缓存，完美支持 PHP7 ，并且倡导以下原则：
	1. 不要 include 变量
	2. 不要采用 eval(), 正则表达式 e 修饰符
	3. 不要采用 autoload
	4. 不要采用 $$var 多重变量
	5. 不要使用 PHP 高级特性 __call __set __get 等魔术方法
	6. 尽量采用函数封装功能，通过前缀区分模块。