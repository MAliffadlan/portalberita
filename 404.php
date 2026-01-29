<?php
/**
 * 404 Not Found Page (WinPoin Style)
 */
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0078d4',
                        darkblue: '#001e3c',
                        secondary: '#2b88d8',
                        accent: '#ff4500',
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    
    <style>body { font-family: 'Segoe UI', 'Inter', sans-serif; }</style>
</head>
<body class="bg-white min-h-screen flex items-center justify-center">
    <div class="text-center px-4">
        <lottie-player 
            src="https://assets9.lottiefiles.com/packages/lf20_kcsr6fcp.json"
            background="transparent"
            speed="1"
            style="width: 300px; height: 300px; margin: 0 auto;"
            loop
            autoplay>
        </lottie-player>
        
        <h1 class="text-6xl font-bold text-primary mb-4">404</h1>
        <h2 class="text-2xl font-semibold text-gray-800 mb-2">Halaman Tidak Ditemukan</h2>
        <p class="text-gray-500 mb-8">Maaf, halaman yang Anda cari tidak ada atau telah dipindahkan.</p>
        
        <div class="flex items-center justify-center space-x-4">
            <a href="index.php" class="px-6 py-3 bg-primary text-white rounded-full font-semibold hover:bg-secondary hover:shadow-lg transition-all transform hover:scale-105">
                <i class="fas fa-home mr-2"></i>Kembali ke Beranda
            </a>
            <button onclick="history.back()" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-full font-semibold hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Halaman Sebelumnya
            </button>
        </div>
    </div>
</body>
</html>

