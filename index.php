<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
setlocale(LC_ALL, 'nl_NL');

function insertVoteValue($connection, $voteValue)
{
    $query = "INSERT INTO votes (Address, ReceivedOn, Value) VALUES ('".selectClientIP()."', '".date('Y/m/d H:i:s')."', '".$voteValue."')";

    if (($result = mysqli_query($connection, $query)) == true)
    {
        return true;
    }

    return false;
}

function resetPreviousVoteValue()
{
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST["vote-reset-value"]))
    {
        return true;
    }

    return false;
}

function selectClientIP()
{
    $ipaddress = '';

    if (getenv('HTTP_CLIENT_IP'))
    {
        $ipaddress = getenv('HTTP_CLIENT_IP');
    }
    else if(getenv('HTTP_X_FORWARDED_FOR'))
    {
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    }
    else if(getenv('HTTP_X_FORWARDED'))
    {
        $ipaddress = getenv('HTTP_X_FORWARDED');
    }
    else if(getenv('HTTP_FORWARDED_FOR'))
    {
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    }
    else if(getenv('HTTP_FORWARDED'))
    {
        $ipaddress = getenv('HTTP_FORWARDED');
    }
    else if(getenv('REMOTE_ADDR'))
    {
        $ipaddress = getenv('REMOTE_ADDR');
    }
    
    return $ipaddress;
}

function selectConnection()
{
    if (strpos($_SERVER['HTTP_HOST'], 'local') !== false)
    {
        return mysqli_connect("localhost", "MJoy", "TBiFdFZpHDEMLcde5SR5", "countdown");
    }

    return mysqli_connect("localhost", "countdown", "OL38muJoxY", "countdown");
}

function selectVoteValueFromDatabase($connection)
{
    $query = "SELECT Value FROM votes WHERE Address = '".selectClientIP()."' AND IsDeleted = 0 LIMIT 1";

    if (($result = mysqli_query($connection, $query)) == true)
    {
        if (($row = mysqli_fetch_object($result)) == true)
        {
            return !empty($row) ? $row->Value : "";
        }
    }

    return "";
}

function selectVoteValueFromForm()
{
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST["vote-value"]))
    {
        return htmlspecialchars(trim($_POST["vote-value"]));
    }

    return "";
}

function selectVoteTotals($connection)
{
    $query = "SELECT V.VotedLeft, CAST(V.VotedLeft / V.Votes * 100 AS DECIMAL(4, 1)) AS VotedLeftPercentage, V.VotedRight, CAST(V.VotedRight / V.Votes * 100 AS DECIMAL(4, 1)) AS VotedRightPercentage, V.Votes
        FROM (
            SELECT SUM(CASE WHEN value = 'left' THEN 1 ELSE 0 END) AS VotedLeft, SUM(CASE WHEN value = 'right' THEN 1 ELSE 0 END) AS VotedRight, COUNT(ID) AS Votes
            FROM countdown.votes
            WHERE IsDeleted = 0
        ) AS V";

    if (($result = mysqli_query($connection, $query)) == true)
    {
        if (($row = mysqli_fetch_object($result)) == true)
        {
            return $row;
        }
    }

    return null;
}

function updateVoteValueToDeleted($connection)
{
    $query = "UPDATE votes SET IsDeleted = 1, DeletedOn = '".date('Y/m/d H:i:s')."' WHERE Address = '".selectClientIP()."' AND IsDeleted = 0";

    if (($result = mysqli_query($connection, $query)) == true)
    {
        return true;
    }

    return false;
}

$connection = selectConnection();

$voteChoiceTextDefault = "En wat denk jij?";
$voteChoiceTextLeft = "Jij denk dat het een MEISJE wordt!";
$voteChoiceTextRight = "Jij denk dat het een JONGEN wordt!";
$voteValue = selectVoteValueFromDatabase($connection);
$voteTotals = null;

if (empty($voteValue))
{
    $voteValue = selectVoteValueFromForm();
    if (!empty($voteValue) && ($voteValue === "left" || $voteValue === "right"))
    {
        if (!insertVoteValue($connection, $voteValue))
        {
            $voteValue = "";
        }
    }
}
else if (resetPreviousVoteValue())
{
    if (updateVoteValueToDeleted($connection))
    {
        $voteValue = "";
    }
}
else
{
    $voteTotals = selectVoteTotals($connection);
}

$votedLeft = !empty($voteValue) && $voteValue === "left" ? true : false;
$votedRight = !empty($voteValue) && $voteValue === "right" ? true : false;

mysqli_close($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="content-type" content="text/html;charset=UTF-8">
    <title>Countdown to that special moment.</title>
    <meta name="author" content="MJoy">
    <meta name="description" content="Countdown to that special upcoming event. Now we are counting down to the showdown of the sex of our unborn child.">
    <meta name="robots" content="NOODP">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="canonical" href="http://countdown.mvesign.com">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="flipclock.min.css">
    <link rel="stylesheet" href="view.min.css">
</head>
<body>
    <div>
        <div id="background-left" <?php echo $votedLeft ? 'class="selected"' : ($votedRight ? 'class="unselected"' : ''); ?>></div>
        <div id="background-right" <?php echo $votedRight ? 'class="selected"' : ($votedLeft ? 'class="unselected"' : ''); ?>></div>
        <div id="clock-container">
            <div id="clock"></div>
        </div>
        <div id="banner-container">
            <div id="banner" style="background-image: url('banner.png');background-repeat: no-repeat;"></div>
        </div>
        <div id="form-container">
            <div id="form">
                <?php 
                if (empty($voteValue))
                {
                ?>
                <div id="vote-choice" class="row text-center <?php echo $votedLeft ? 'vote-left' : ($votedRight ? 'vote-right' : ''); ?>">
                    <span><?php echo $votedLeft ? $voteChoiceTextLeft : ($votedRight ? $voteChoiceTextRight : $voteChoiceTextDefault); ?></span>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <button class="btn btn-left btn-primary" id="vote-left" name="vote-left" type="button">
                            <span class="fa fa-venus"></span>
                        </button>
                    </div>
                    <div class="col-md-4"></div>
                    <div class="col-md-4">
                        <button class="btn btn-right btn-primary" id="vote-right" name="vote-right" type="button">
                            <span class="fa fa-mars"></span>
                        </button>
                    </div>
                </div>
                <?php
                }
                else if ($voteTotals != null)
                {
                ?>
                <div id="vote-totals" class="row">
                    <div class="row <?php echo $votedLeft ? 'selected': ''; ?>">
                        <div class="col-md-2">Meisje</div>
                        <div class="col-md-8">
                            <div class="vote-left" style="width: <?php echo $voteTotals->VotedLeftPercentage; ?>%;">&nbsp;</div>
                        </div>
                        <!--<div class="col-md-2"><?php echo $voteTotals->VotedLeftPercentage.'% ('.$voteTotals->VotedLeft.')'; ?></div>-->
                        <div class="col-md-2"><?php echo $voteTotals->VotedLeft; ?></div>
                    </div>
                    <div class="row <?php echo $votedRight ? 'selected': ''; ?>">
                        <div class="col-md-2">Jongen</div>
                        <div class="col-md-8">
                            <div class="vote-right" style="width: <?php echo $voteTotals->VotedRightPercentage; ?>%;">&nbsp;</div>
                        </div>
                        <!--<div class="col-md-2"><?php echo $voteTotals->VotedRightPercentage.'% ('.$voteTotals->VotedRight.')'; ?></div>-->
                        <div class="col-md-2"><?php echo $voteTotals->VotedRight; ?></div>
                    </div>
                </div>
                <?php
                }
                ?>
                <div class="row">
                    <div class="col-md-4"></div>
                    <div class="col-md-4">
                        <?php
                        if (!empty($voteValue))
                        {
                        ?>
                        <button class="btn btn-primary btn-submit fa fa-check-square-o selected" id="vote-submit" name="vote-submit" title="Bedankt! Jouw keuze is vastgelegd!" type="button" disabled></button>
                        <?php
                        }
                        else
                        {
                        ?>
                        <button class="btn btn-primary btn-submit fa fa-check-square-o" id="vote-submit" name="vote-submit" title="En wat denk jij? Maak snel je keuze!" type="button" disabled></button>
                        <?php
                        }
                        ?>
                    </div>
                    <div class="col-md-4"></div>
                </div>
                <form action="/" id="form-vote" method="POST" name="form-vote">
                    <?php
                    if (empty($voteValue))
                    {
                    ?>
                    <input id="vote-value" name="vote-value" type="hidden" value="" />
                    <?php
                    }
                    else
                    {
                    ?>
                    <button class="btn btn-link" id="vote-reset" name="vote-reset" type="button">Wil je jouw keuze aanpassen? Klik dan hier!</button>
                    <input id="vote-reset-value" name="vote-reset-value" type="hidden" value="1" />
                    <?php
                    }
                    ?>
                </form>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="flipclock.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function()
        {
            FlipClock.Lang.Custom = { days:'dagen', hours:'uren', minutes:'minuten', seconds:'seconden' };

            var countdown = 1482584400 - ((new Date().getTime())/1000);
            countdown = Math.max(1, countdown);

            var clock = $('#clock').FlipClock(
            {
                clockFace: 'DailyCounter',
                language: 'Custom'
            });
            
            clock.setTime(countdown);
		    clock.setCountdown(true);
		    clock.start();
        });
	</script>
    <?php
    if (empty($voteValue))
    {
    ?>
    <script type="text/javascript">
        $(document).ready(function()
        {
            $("#vote-left").click(function()
            {
                $("#background-left").removeClass("unselected");
                $("#background-right").removeClass("selected");
                $("#vote-choice").removeClass("vote-right");

                $("#background-left").toggleClass("selected");
                $("#vote-choice").toggleClass("vote-left");

                if ($("#background-left" ).hasClass("selected"))
                {
                    $("#background-right").addClass("unselected");
                    $("#vote-choice").text("<?php echo $voteChoiceTextLeft; ?>");
                    $("#vote-submit").prop("disabled", false);
                    $("#vote-value").val("left");
                }
                else
                {
                    $("#background-right").removeClass("unselected");
                    $("#vote-choice").text("<?php echo $voteChoiceTextDefault; ?>");
                    $("#vote-submit").prop("disabled", true);
                    $("#vote-value").val("");
                }
            });

            $("#vote-right").click(function()
            {
                $("#background-left").removeClass("selected");
                $("#background-right").removeClass("unselected");
                $("#vote-choice").removeClass("vote-left");

                $("#background-right").toggleClass("selected");
                $("#vote-choice").toggleClass("vote-right");

                if ($("#background-right" ).hasClass("selected"))
                {
                    $("#background-left").addClass("unselected");
                    $("#vote-choice").text("<?php echo $voteChoiceTextRight; ?>");
                    $("#vote-submit").prop("disabled", false);
                    $("#vote-value").val("right");
                }
                else
                {
                    $("#background-left").removeClass("unselected");
                    $("#vote-choice").text("<?php echo $voteChoiceTextDefault; ?>");
                    $("#vote-submit").prop("disabled", true);
                    $("#vote-value").val("");
                }
            });

            $("#vote-submit").click(function()
            {
                var voteValue = $("#vote-value").val();
                if (voteValue === "left" || voteValue === "right")
                {
                    $("#form-vote").submit();
                }
            });
        });
    </script>
    <?php
    }
    else
    {
    ?>
    <script type="text/javascript">
        $(document).ready(function()
        {
            $("#vote-reset").click(function()
            {
                $("#form-vote").submit();
            });
        });
    </script>
    <?php
    }
    ?>
</body>
</html>