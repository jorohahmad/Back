<?php

function saveFile($file, $folder)
{
    $extension = $file->getClientOriginalExtension();
    $fileName = time() . rand(1, 1000) . '.' . $extension;
    $s = $file->storeAs($folder, $fileName, 'public');
    return $s;
}

function saveTempFile($file)
{
    $extension = $file->getClientOriginalExtension();
    $fileName = 'temp_' . time() . '_' . rand(1, 1000) . '.' . $extension;
    // نحفظ في الـ local وليس الـ public
    return $file->storeAs('temp_uploads', $fileName, 'local'); 
}