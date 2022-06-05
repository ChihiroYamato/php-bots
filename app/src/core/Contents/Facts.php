<?php

namespace Anet\App\Contents;

/**
 * **Facts** class wrapper for fetch text by category "facts" from DB
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
final class Facts extends Texts
{
    protected const CATEGORY_NAME = 'facts';
    protected const WARNING_MESSAGE = 'Сорри, что-то пошло не так, факт будет в другой раз:(';

}
