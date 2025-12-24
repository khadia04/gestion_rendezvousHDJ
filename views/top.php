<meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="../assets/img/favicon.png" rel="icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../assets/vendor/animate.css/animate.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="../assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">


    <!-- Template Main CSS File -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/site.css" rel="stylesheet" />
</head>
<body>
    
<!-- ======= Top Bar ======= -->
    <div id="topbar" class="d-flex align-items-center fixed-top">
        <div class="container d-flex justify-content-between">
            <div class="contact-info d-flex align-items-center">
                <i class="bi bi-envelope"></i> <a href="mailto:contact@example.com">hdj@dalaljamm.com</a>
                <i class="bi bi-phone"></i> +221 33 839 85 85 
            </div>
            <div class="d-none d-lg-flex social-links align-items-center">
                <a href="https://x.com/jammdalal?s=11&t=eqfBrg0Xp6U8iTilHyQtLA" class="twitter" target="_blank"><i class="bi bi-twitter" title="Twitter"></i></a>
                <a href="https://www.facebook.com/profile.php?id=100057313005499&mibextid=LQQJ4d" target="_blank" class="facebook"><i class="bi bi-facebook" title="Facebook"></i></a>
                <a href="https://instagram.com/hopital_dalal_jamm?igshid=MzMyNGUyNmU2YQ==" target="_blank" class="instagram"><i class="bi bi-instagram" title="Instagramm"></i></a>
                <a href="https://www.linkedin.com/company/h%C3%B4pital-dalal-jamm/" target="_blank" class="linkedin"><i class="bi bi-linkedin" title="LinkedIn"></i></a>
				<a class="text-dark"> Uilisateur connecté </a>
			   <?php
					session_start();
					if (!isset($_SESSION['username']) || (isset($_SESSION['lastAction']) and (time() - $_SESSION['timeframe']) > $_SESSION['lastAction'])  ) {
						echo "<script>location.href='logout.php'</script>";   // Fermeture de Session Forcee
						exit;
					}
					else {
						$_SESSION['lastAction'] = time(); // Mise à jour de la variable derniere action
						echo "<a href='profile.php'>".$_SESSION['username']."</a>";
					}
				?>
                <a class="btn btn-danger text-white ml-5" style="margin-left:30px;" href="logout.php">
                    <i class="fas fa-power-off d-block" title="Déconnexion"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ======= Header ======= -->
    <header id="header" class="fixed-top">
        <div class="container d-flex align-items-center">

            <h1 class="logo me-auto"><a href="index.html">Rendez Vous H.D.j</a></h1>
            <!-- Uncomment below if you prefer to use an image logo -->
            <!-- <a href="index.html" class="logo me-auto"><img src="../assets/img/logo.png" alt="" class="img-fluid"></a>-->

            <nav id="navbar" class="navbar order-last order-lg-0 text-left">
                <ul>
                    <li><a href="home.php" class="nav-link scrollto active">Accueil</a></li>
                </ul>
                <i class="bi bi-list mobile-nav-toggle"></i>
            </nav><!-- .navbar -->

            <a href="searchpatient.php" class="appointment-btn scrollto">Rechercher un Patient</a>
            <a href="addpatient.php" class="appointment-btn scrollto">Nouveau Patient</a>
			<a href="showrv.php" class="appointment-btn scrollto">Liste RV</a>
            <?php
                if (isset($_SESSION['username']) && $_SESSION['username'] == "admin") {
                    echo '<a href="admin.php" class="appointment-btn scrollto">Admin</a>';
                }                
            ?>
        </div>
    </header><!-- End Header -->