<ul>
    @foreach ($dirs as $path=>$info)
    <li style="list-style: none">
        <?php
        if ($info['icon'] == "play"){
            ?>
            <a style="cursor:pointer;<?php if ($nbmp3 == 0){echo "display:none";}?>" onclick="lanceExplorer()"><i class="fa fa-{{$info['icon']}}"></i>&nbsp;&nbsp;- Jouer <?php echo basename($root);?>-</a>
            <?php
        }else{
            ?>
            <a style="cursor:pointer" onclick="loadDirectories('{{urlencode($path)}}')"><i class="fa fa-{{$info['icon']}}"></i>&nbsp;&nbsp;{{$info['dir']}}</a>
            <?php
        }
        ?>
    </li>
    @endforeach
</ul>

<textarea id="explorer_mp3" style="display:none">
oPlaylist = [
    <?php
    $i = 0;
    foreach ($dirs as $path=>$info){
        if ($info['icon'] == "file" && $info["dir"] != ".."){
            $i++;
            ?>
            {
                title:<?php echo json_encode($info['dir']);?>,
                mp3:<?php echo json_encode(env("APP_URL")."/mp3?url=".urlencode($path));?>
            }
        <?php
            if ($i<count($dirs)){
                echo ",";
            }
        }
    }
    ?>
]
</textarea>
