<?php

namespace Anet\App\Contents;

/**
 * **Jokes** class wrapper for fetch text by category "jokes" from DB
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
class Jokes extends Texts
{
    protected const CATEGORY_NAME = 'jokes';
    protected const WARNING_MESSAGE = 'Сорри, что-то пошло не так, шутка будет в другой раз:(';
}
