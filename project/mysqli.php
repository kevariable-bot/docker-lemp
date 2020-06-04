<?php

  $db = new mysqli(
    $_ENV['MYSQL_HOST'],
    $_ENV['MYSQL_USER'],
    $_ENV['MYSQL_PASSWORD'],
    // Dont forget create database 'mynewdb'
    $_ENV['MYSQL_DATABASE'],
    $_ENV['MYSQL_PORT'] ?? 3306
  );

  var_dump($db);

  // $query = mysqli_query($db, "SELECT * FROM test");

  // var_dump(mysqli_fetch_assoc($query));