<?php
// languages
echo '
    &emsp;&emsp;&emsp;
    <b>'.$got_lang["MenLa"].' </b>';
    $handle = opendir('./languages');
    while ($file = readdir($handle))
    {    if (!is_dir($file) AND substr($file,-3) == 'php')
        {   $Lang = substr($file,0,2);
            $IB = 'IB'.$Lang;
            echo '<a href='.$page;
            url_post();
            echo '&lang='.$Lang.'><img src=languages/'.$Lang.'.png width=35 height=22><span>'.$got_lang[$IB].'</span></a>&nbsp;';
        }
    }

// themes
echo '
    &emsp;&emsp;&emsp;
    <b>'.$got_lang["MenTh"].' </b>';
    $handle = opendir('./themes');
    while ($file = readdir($handle))
    {    if (!is_dir($file) AND substr($file,-3) == 'css')
        {    $CSS = substr($file, 0, strpos($file,"."));
            echo '<a href='.$page;
            url_post();
            echo '&theme='.$CSS.'><img border=0 src=themes/'.$CSS.'.png width=35 height=22><span>'.$CSS.'</span></a>&nbsp;';
            if ($CSS == $_REQUEST["theme"]) 
            {   $content = file_get_contents("themes/".$file);
                $RGB = substr($content, strpos ($content,"#") + 1, 6);
            }
        }
    }
?>