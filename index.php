<?php

// Root entrypoint for production hosting.
// Redirect visitors to the frontend landing page.
header('Location: /frontend/index.html', true, 302);
exit;

