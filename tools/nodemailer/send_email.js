const nodemailer = require('nodemailer');

const args = process.argv.slice(2);
if (args.length < 4) {
    console.error("Usage: node send_email.js <nama> <username> <role> <app_password>");
    process.exit(1);
}

const nama = args[0];
const username = args[1];
const role = args[2];
const appPassword = args[3];

const superadminEmail = 'muhammaddq16@gmail.com';

const transporter = nodemailer.createTransport({
    service: 'gmail',
    auth: {
        user: superadminEmail,
        pass: appPassword
    }
});

let message = "Halo Superadmin,\n\n";
message += "Ada permintaan reset password dari pengguna berikut di aplikasi AKRAB (Via Nodemailer):\n\n";
message += "Nama: " + nama + "\n";
message += "Username / NISN: " + username + "\n";
message += "Peran: " + role + "\n\n";
message += "Silakan login ke panel admin untuk mereset password pengguna ini secara manual.\n\n";
message += "Terima kasih,\nSistem Automasi AKRAB";

const mailOptions = {
    from: `"Sistem AKRAB" <${superadminEmail}>`,
    to: superadminEmail,
    subject: 'Permintaan Reset Password - AKRAB',
    text: message
};

transporter.sendMail(mailOptions, function(error, info){
    if (error) {
        console.error(error.toString());
        process.exit(1);
    } else {
        console.log('Email sent: ' + info.response);
        process.exit(0);
    }
});
