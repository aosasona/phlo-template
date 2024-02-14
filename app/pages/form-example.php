<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../phlo/Extensions/functions.php';

use function Phlo\Extensions\csrf_token;

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="description" content="Phlo" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="/public/css/style.css" />
  <title>Form Example</title>
</head>

<body class="form-example">
  <pre id="result">No result yet...</pre>

  <p>This form demonstrates how to use the csrf_token() function to generate a CSRF token and include it in a form, and how to directly use the API routes to handle the form submission.</p>
  <form method="post" action="/api/form-example" class="csrf-sample">
    <?php csrf_token(); ?>
    <input type="text" name="name" placeholder="Name" required>
    <button type="submit">Submit</button>
  </form>

  <p> This form has no CSRF token, so it will fail when submitted. </p>
  <form method="post" action="/api/form-example" class="csrf-sample">
    <input type="text" name="name" placeholder="Name" required>
    <button type="submit">Submit</button>
  </form>
</body>

<script>
  // progressively enhance the form with JavaScript so we can see the error message without leaving the page

  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      e.stopPropagation();

      const formData = new FormData(form);
      const response = await fetch(form.action, {
        method: form.method,
        body: formData
      });

      const result = await response.json();
      document.getElementById('result').innerText = JSON.stringify(result, null, 2);
    });
  });
</script>

</html>
