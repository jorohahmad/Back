<?php

function saveFile($file, $folder)
{
    $extension = $file->getClientOriginalExtension();
    $fileName = time() . rand(1, 1000) . '.' . $extension;
    $s = $file->storeAs($folder, $fileName, 'public');
    return $s;
}

