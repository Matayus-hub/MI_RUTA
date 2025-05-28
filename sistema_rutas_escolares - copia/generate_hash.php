<?php
// generate_hash.php
$password_admin = 'admin123';
$password_estudiante = 'estudiante123';
$password_conductor = 'conductor123';

echo "Hash para 'admin123': " . password_hash($password_admin, PASSWORD_DEFAULT) . "<br>";
echo "Hash para 'estudiante123': " . password_hash($password_estudiante, PASSWORD_DEFAULT) . "<br>";
echo "Hash para 'conductor123': " . password_hash($password_conductor, PASSWORD_DEFAULT) . "<br>";
?>