<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PlantCare</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <?php include 'pages/auth.php'; ?>

  <div id="appScreen" style="display:none;height:100vh;flex-direction:row;">
    <?php include 'pages/sidebar.php'; ?>
    <main class="main">
      <?php include 'pages/dashboard.php'; ?>
      <?php include 'pages/piante.php'; ?>
      <?php include 'pages/allarmi.php'; ?>
      <?php include 'pages/grafici.php'; ?>
      <?php include 'pages/aggiungi.php'; ?>
      <?php include 'pages/impostazioni.php'; ?>
    </main>
  </div>

  <?php include 'pages/overlays.php'; ?>

  <script src="js/app.js?v=2"></script>
  <script src="js/ui.js?v=2"></script>
  <script src="js/pages.js?v=2"></script>

</body>
</html>
