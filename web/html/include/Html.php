<?php
Class HTML {
	# Setup
	function __construct($DS='seamless') {
		$this->displayName = "<div id='SWAMID-SeamlessAccess'></div>";
		$this->destination = '';
		$this->startTimer = time();
		switch ($DS) {
			case 'seamless' :
				$this->DS = '/DS/seamless-access';
				$this->DSService= '//service.seamlessaccess.org/thiss.js';
				break;
			case 'thiss' :
				$this->DS = '/DS/thiss.io';
				$this->DSService= '//use.thiss.io/thiss.js';
				break;
			default :
				$this->DS = '/DS/seamless-access';
				$this->DSService= '//service.seamlessaccess.org/thiss.js';
		}
	}

	###
	# Print start of webpage
	###
	public function showHeaders($title = "") {
		header('Content-Type: text/html; charset=utf-8'); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?=$title?></title>
  <link href="/fontawesome/css/fontawesome.min.css" rel="stylesheet">
  <link href="/fontawesome/css/solid.min.css" rel="stylesheet">
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
  <link rel="manifest" href="/images/site.webmanifest">
  <link rel="mask-icon" href="/images/safari-pinned-tab.svg" color="#5bbad5">
  <link rel="shortcut icon" href="/images/favicon.ico">
  <meta name="msapplication-TileColor" content="#da532c">
  <meta name="msapplication-config" content="/images/browserconfig.xml">
  <meta name="theme-color" content="#ffffff">
  <meta name="viewport" content="initial-scale=1, maximum-scale=1"/>
  <style>
    /* Space out content a bit */
    body {
      padding-top: 20px;
      padding-bottom: 20px;
    }

    .text-truncate {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      display: inline-block;
      max-width: 100%;
    }

    /* color for fontawesome icons */
    .fa-check {
      color: green;
    }
    .fa-exclamation-triangle {
      color: orange;
    }
    .fa-exclamation {
      color: red;
    }

    /* Customize container */
    @media (min-width: 768px) {
      .container {
        max-width: 1800px;
      }
    }
    .container-narrow > hr {
      margin: 30px 0;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow">
      <h3 class="my-0 mr-md-auto font-weight-normal">
        <a class="brand" href="/">
          <svg width="75" height="95" viewBox="0 0 30 38" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Till startsidan">
            <title>Till startsidan</title>
            <mask id="mask0" mask-type="alpha" maskUnits="userSpaceOnUse" x="0" y="31" width="12" height="7">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M0.649658 31.2283H11.3042V37.9974H0.649658V31.2283Z" fill="white"></path>
            </mask>
            <g mask="url(#mask0)">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M1.47851 33.0773C1.47851 33.2693 1.51693 33.4279 1.5938 33.553C1.67067 33.6783 1.77398 33.7815 1.9038 33.8628C2.03349 33.9442 2.18411 34.0109 2.35547 34.0629C2.52684 34.1149 2.70547 34.1637 2.89129 34.2092C3.14111 34.2711 3.38702 34.3394 3.62887 34.4142C3.87072 34.489 4.08622 34.5922 4.27521 34.7239C4.4642 34.8557 4.61719 35.0273 4.73411 35.2387C4.85107 35.4501 4.90957 35.7216 4.90957 36.0534C4.90957 36.3917 4.84622 36.6836 4.71957 36.9292C4.59292 37.1748 4.4179 37.3764 4.19433 37.5341C3.9708 37.6919 3.70644 37.809 3.40124 37.8854C3.096 37.9618 2.7619 38 2.39891 38C2.23186 38 2.0608 37.9895 1.88574 37.9683C1.71058 37.9472 1.54358 37.9187 1.38455 37.883C1.22556 37.8472 1.08173 37.8066 0.953271 37.761C0.824768 37.7155 0.723535 37.67 0.649658 37.6244V36.8975C0.765341 36.956 0.895429 37.0097 1.04001 37.0585C1.18455 37.1073 1.33398 37.1487 1.48816 37.1829C1.64239 37.217 1.79816 37.2438 1.95561 37.2634C2.11296 37.2828 2.26076 37.2926 2.39891 37.2926C2.62376 37.2926 2.8382 37.2731 3.04221 37.2341C3.24622 37.195 3.42534 37.13 3.57953 37.0389C3.73376 36.9479 3.85578 36.8235 3.94578 36.6657C4.03569 36.508 4.08072 36.3104 4.08072 36.0729C4.08072 35.8778 4.04133 35.7168 3.96265 35.5899C3.88389 35.463 3.77869 35.3582 3.64702 35.2753C3.51525 35.1923 3.36345 35.1248 3.19164 35.0727C3.01975 35.0208 2.83904 34.972 2.64948 34.9264C2.39891 34.8679 2.15393 34.802 1.91464 34.7288C1.67525 34.6556 1.46168 34.554 1.27371 34.4239C1.08578 34.2939 0.934768 34.1263 0.82076 33.9214C0.706663 33.7165 0.649658 33.4547 0.649658 33.1359C0.649658 32.8041 0.709526 32.5188 0.829438 32.2796C0.949306 32.0406 1.11146 31.843 1.31613 31.6869C1.52076 31.5308 1.75635 31.4154 2.02305 31.3405C2.28966 31.2657 2.57398 31.2283 2.876 31.2283C3.2197 31.2283 3.54019 31.2617 3.83737 31.3283C4.13446 31.395 4.40675 31.482 4.65415 31.5893V32.326C4.38746 32.2122 4.11204 32.1195 3.82772 32.0479C3.5434 31.9765 3.239 31.939 2.91455 31.9357C2.67036 31.9357 2.45834 31.9625 2.27847 32.0162C2.09851 32.0698 1.94909 32.1463 1.83032 32.2455C1.71142 32.3448 1.62309 32.4651 1.56525 32.6066C1.50746 32.748 1.47851 32.905 1.47851 33.0773Z" fill="#1D1C1A"></path>
              <path fill-rule="evenodd" clip-rule="evenodd" d="M8.7889 37.2926C9.02335 37.2926 9.23141 37.265 9.41295 37.2097C9.59441 37.1544 9.75264 37.0763 9.88758 36.9755C10.0225 36.8747 10.1349 36.7535 10.2249 36.612C10.3148 36.4705 10.3839 36.3136 10.4321 36.1412C10.4642 36.0339 10.4867 35.9144 10.4996 35.7826C10.5124 35.6509 10.5189 35.5233 10.5189 35.3996V31.3502H11.3043V35.3801C11.3043 35.533 11.2963 35.6923 11.2802 35.8583C11.2641 36.0241 11.2385 36.1786 11.2031 36.3217C11.1421 36.5625 11.0505 36.7852 10.9285 36.9901C10.8063 37.195 10.6473 37.3723 10.4514 37.5219C10.2554 37.6716 10.0201 37.7886 9.74542 37.8732C9.47075 37.9577 9.14864 38 8.77925 38C8.42582 38 8.11582 37.961 7.84921 37.8829C7.58256 37.8049 7.35361 37.6968 7.16251 37.5585C6.97137 37.4203 6.81476 37.256 6.69269 37.0657C6.57057 36.8754 6.47582 36.6681 6.40837 36.4437C6.36018 36.2811 6.32564 36.107 6.3048 35.9217C6.28383 35.7362 6.27344 35.5558 6.27344 35.3801V31.3502H7.05894V35.3996C7.05894 35.546 7.06934 35.6965 7.09027 35.8509C7.1111 36.0054 7.14401 36.1461 7.18903 36.273C7.30146 36.5885 7.48855 36.8373 7.75044 37.0194C8.0122 37.2016 8.35837 37.2926 8.7889 37.2926Z" fill="#1D1C1A"></path>
            </g>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M16.2292 34.9898C16.6244 35.5655 17.0147 36.1575 17.4002 36.7657H17.4291C17.3873 36.1314 17.3665 35.4842 17.3665 34.8239V31.3502H18.1568V37.8781H17.3713L14.8415 34.2384C14.4045 33.6042 14.0142 33.0123 13.6705 32.4626H13.6416C13.6833 33.0578 13.7042 33.7522 13.7042 34.5458V37.8781H12.9187V31.3502H13.6994L16.2292 34.9898Z" fill="#1D1C1A"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M20.5757 37.1609H23.8815V37.8781H19.7903V31.3502H23.7032V32.0674H20.5757V34.1751H23.3562V34.8922H20.5757V37.1609Z" fill="#1D1C1A"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M27.5438 37.8781H26.7583V32.0674H24.6042V31.3502H29.6977V32.0674H27.5438V37.8781Z" fill="#1D1C1A"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.22449 15.3644V3.63203C3.22449 3.54653 3.40511 3.3064 3.54687 3.3064H29.9235V0.0408936C19.4294 0.0408936 3.54687 0.0408936 3.54687 0.0408936C2.22247 0.0408936 0 0.507732 0 3.63203V15.3644C0 17.5793 1.3585 18.9557 3.54687 18.9557H19.9275V15.6909H3.54687C3.39665 15.6909 3.22449 15.5167 3.22449 15.3644Z" fill="#D3452E"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M26.7392 11.4358V23.168C26.7392 23.2536 26.5585 23.4938 26.4169 23.4938H0.0402832V26.7591C10.5344 26.7591 26.4169 26.7591 26.4169 26.7591C27.7413 26.7591 29.9639 26.2922 29.9639 23.168V11.4358C29.9639 9.22087 28.6053 7.84448 26.4169 7.84448H10.0363V11.1093H26.4169C26.5669 11.1093 26.7392 11.2835 26.7392 11.4358Z" fill="#D3452E"></path>
          </svg>
        </a>User verification
      </h3>
      <nav class="my-2 my-md-0 mr-md-3">
        <a class="p-2 text-dark" href="https://sunet.se/om-sunet">Om Sunet</a>
        <a class="p-2 text-dark" href="https://sunet.se/services">Tjänster</a>
        <a class="p-2 text-dark" href="https://sunet.se/kontakt">Kontakt</a>
      </nav>
      <?=$this->displayName?> 
    </div>
 <?php	}

###
# Print footer on webpage
###
public function showFooter($collapseIcons = array(), $seamless = false) {
	$hostURL = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];
	// printf('    <hr>%s    %d%s', "\n", time()-$this->startTimer, "\n");
	?>
  </div><?php if ($seamless) { ?>

  <!-- Include the Seamless Access Sign in Button & Discovery Service -->
  <script src="<?=$this->DSService?>"></script>
  <script>
    window.onload = function() {
      // Render the Seamless Access button
      thiss.DiscoveryComponent({
        loginInitiatorURL: '<?=$hostURL?>/Shibboleth.sso<?=$this->DS?>?target=<?=$hostURL?>/admin/<?=$this->destination?>'
      }).render('#SWAMID-SeamlessAccess');
    };
  </script><?php } ?>

  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  <script>
    $(function () {<?php
	if (isset($collapseIcons)) {
		foreach ($collapseIcons as $collapseIcon) { ?>

      $('#<?=$collapseIcon?>').on('show.bs.collapse', function (event) {
        var tag_id = document.getElementById('<?=$collapseIcon?>-icon');
        tag_id.className = "fas fa-chevron-circle-down";
        event.stopPropagation();
      })
      $('#<?=$collapseIcon?>').on('hide.bs.collapse', function (event) {
        var tag_id = document.getElementById('<?=$collapseIcon?>-icon');
        tag_id.className = "fas fa-chevron-circle-right";
        event.stopPropagation();
      })<?php		}
	} ?>

    })
    // Add the following code if you want the name of the file appear on select
    $(".custom-file-input").on("change", function() {
      //var fileName = $(this).val().split("\\").pop();
      var fileName = $(this).val().split("\\\\").pop();
      $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
  </script>
</body>
</html>
<?php
	}

	public function setDisplayName($name) {
		$this->displayName = $name;
	}

	public function setDestination($destination) {
		$this->destination = $destination;
	}
}