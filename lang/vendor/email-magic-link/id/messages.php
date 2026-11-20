<?php

declare(strict_types=1);

return [
    // Shared.
    'heading' => 'Masuk ke :app',
    'email_label' => 'Alamat email',
    'sign_in' => 'Masuk',

    // Request form.
    'request_title' => 'Masuk',
    'request_intro_link' => 'Masukkan alamat email Anda dan kami akan mengirim tautan masuk yang aman.',
    'request_intro_code' => 'Masukkan alamat email Anda dan kami akan mengirim kode masuk yang aman.',
    'request_send_link' => 'Kirim tautan masuk',
    'request_send_code' => 'Kirim kode masuk',
    'delivery_legend' => 'Pengiriman',
    'delivery_link' => 'Magic link',
    'delivery_code' => 'Kode sekali pakai',

    // Confirmation page.
    'confirm_title' => 'Konfirmasi masuk',
    'confirm_intro' => 'Demi keamanan, konfirmasi bahwa Anda ingin masuk.',

    // Code entry form.
    'code_title' => 'Masukkan kode',
    'code_heading' => 'Masukkan kode masuk Anda',
    'code_intro' => 'Kami mengirim kode sekali pakai ke email Anda. Masukkan di bawah untuk menyelesaikan masuk.',
    'code_label' => 'Kode masuk',

    // Confirmation passphrase gate.
    'passphrase_label' => 'Kata sandi tambahan',

    // "Stay signed in" on the confirmation screen and the code form.
    'remember_label' => 'Tetap masuk',

    // Invalid link page.
    'invalid_title' => 'Permintaan masuk tidak valid',

    // Status and error messages.
    'status_link_sent' => 'Jika ada akun yang cocok dengan email tersebut, kami telah mengirim tautan masuk.',
    'status_code_sent' => 'Jika ada akun yang cocok dengan email tersebut, kami telah mengirim kode masuk.',
    'consume_failed' => 'Permintaan masuk ini tidak valid atau sudah kedaluwarsa. Silakan minta yang baru.',
    'request_new_code' => 'Minta kode masuk baru',
    'back_to_sign_in' => 'Kembali ke halaman masuk',
    'invitation_failed' => 'Undangan ini tidak valid atau sudah kedaluwarsa. Minta undangan baru kepada pengundang.',
    'captcha_failed' => 'Tantangan verifikasi gagal. Silakan coba lagi.',
    'resend_throttled' => '{1} Harap tunggu :seconds detik sebelum meminta email masuk lagi.|[0,*] Harap tunggu :seconds detik sebelum meminta email masuk lagi.',
    'resend_countdown_label' => 'Waktu tunggu sebelum Anda dapat meminta email lagi',

    // Notification - magic link.
    'mail_link_subject' => 'Masuk ke :app',
    'mail_link_intro' => 'Gunakan tombol di bawah untuk masuk ke :app.',
    'mail_link_action' => 'Masuk',
    'mail_link_expiry' => '{1} Tautan ini kedaluwarsa dalam :minutes menit dan dapat digunakan sekali.|[0,*] Tautan ini kedaluwarsa dalam :minutes menit dan dapat digunakan sekali.',
    'mail_link_expiry_reusable' => '{1} Tautan ini kedaluwarsa dalam :minutes menit dan dapat digunakan :uses kali.|[0,*] Tautan ini kedaluwarsa dalam :minutes menit dan dapat digunakan :uses kali.',

    // Notification - one-time code.
    'mail_code_subject' => 'Kode masuk :app Anda',
    'mail_code_intro' => 'Kode masuk Anda untuk :app adalah:',
    'mail_code_expiry' => '{1} Kode ini kedaluwarsa dalam :minutes menit.|[0,*] Kode ini kedaluwarsa dalam :minutes menit.',

    // Notification - shared.
    'mail_greeting' => 'Halo!',
    'mail_salutation' => 'Salam, :app',
    'mail_ignore' => 'Jika Anda tidak meminta ini, Anda dapat mengabaikan email ini.',
];
