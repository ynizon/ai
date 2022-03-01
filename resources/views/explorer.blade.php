<ul>
    @foreach ($dirs as $path=>$info)
    <li style="list-style: none">
        <?php
        switch ($info['icon']){
            case "play":
                ?>
                <a style="cursor:pointer;<?php if ($nbmp3 == 0){echo "display:none";}?>" onclick="lanceExplorer('')"><i class="fa fa-{{$info['icon']}}"></i>&nbsp;&nbsp;- Jouer <?php echo basename($root);?>-</a>
                <?php
                break;

            case "heart":
                ?>
                <a style="cursor:pointer;<?php if ($nbmp3 == 0){echo "display:none";}?>" onclick="lanceExplorer('heart')"><i class="fa fa-{{$info['icon']}}"></i>&nbsp;&nbsp;- Jouer <?php echo basename($root);?>-</a>
                <?php
                break;

            default:
                ?>
                <a style="cursor:pointer" onclick="loadDirectories('{{urlencode($path)}}')"><i class="fa fa-{{$info['icon']}}"></i>&nbsp;&nbsp;{{$info['dir']}}</a>
                <?php
                break;
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


<textarea id="explorer_mp3heart" style="display:none">
oPlaylist = [
    <?php
    $i = 0;
    foreach ($dirs as $path=>$info){
        if ($info["dir"] == "favorites.m3u"){
            foreach ($info["files"] as $file){
                $i++;
                ?>
                {
                    title:<?php echo json_encode($file['dir']);?>,
                    mp3:<?php echo json_encode(env("APP_URL")."/mp3?url=".urlencode($file['path']));?>
                }
                <?php
                if ($i<count($info["files"])){
                    echo ",";
                }
            }
        }
    }
    ?>
]
</textarea>
