<?php
echo password_hash("qydentra.recep", PASSWORD_BCRYPT);   // for receptionist
echo password_hash('qydentra.admin', PASSWORD_DEFAULT);   // for admin
echo password_hash('qydentra.dentist', PASSWORD_DEFAULT); // for dentist
?>

<?php
echo password_hash("qydentra.receptionist", PASSWORD_BCRYPT);?>