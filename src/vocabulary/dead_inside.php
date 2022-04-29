<?php

function deadInside() : array
{
    $result = ['response' => []];

    for ($i = 993; $i > 0; $i -=7) {
        $result['response'][] = (string) $i;
    }

    return $result;
}

return deadInside();
