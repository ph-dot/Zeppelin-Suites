<?php
/**
 * Legacy Login Redirect Bridge
 * Forwards requests landing on the old procedural script to the centralized MVC entry point.
 */
header("Location: ../login", true, 301);
exit;