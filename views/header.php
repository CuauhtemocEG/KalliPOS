<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>POS Kalli Jaguar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="../assets/Logo.jpg" type="image/jpg">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            'montserrat': ['Montserrat', 'system-ui', 'sans-serif'],
            'inter': ['Inter', 'system-ui', 'sans-serif'],
            'space': ['Space Grotesk', 'system-ui', 'sans-serif']
          },
          colors: {
            primary: {
              50: '#f0f9ff',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              900: '#1e3a8a'
            },
            dark: {
              50: '#f8fafc',
              100: '#f1f5f9',
              200: '#e2e8f0',
              300: '#cbd5e1',
              400: '#94a3b8',
              500: '#64748b',
              600: '#475569',
              700: '#334155',
              800: '#1e293b',
              900: '#0f172a'
            }
          },
          animation: {
            'fade-in': 'fadeIn 0.5s ease-in-out',
            'slide-up': 'slideUp 0.3s ease-out',
            'pulse-slow': 'pulse 3s infinite'
          }
        }
      }
    }
  </script>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Custom Styles -->
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .glass-effect {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .card-hover {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .gradient-text {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .dark .gradient-text {
      background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    body {
      font-family: 'Montserrat', system-ui, sans-serif;
      font-weight: 400;
    }
    
    .font-display {
      font-family: 'Montserrat', system-ui, sans-serif;
      font-weight: 700;
    }
    
    .font-inter {
      font-family: 'Inter', system-ui, sans-serif;
    }
    
    .font-space {
      font-family: 'Space Grotesk', system-ui, sans-serif;
    }
    
    /* Montserrat weight utilities */
    .font-montserrat-light { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 300; }
    .font-montserrat-regular { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 400; }
    .font-montserrat-medium { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 500; }
    .font-montserrat-semibold { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 600; }
    .font-montserrat-bold { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 700; }
    .font-montserrat-extrabold { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 800; }
    .font-montserrat-black { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 900; }
  </style>
</head>

<body class="dark bg-gradient-to-br from-dark-900 via-dark-800 to-dark-900 min-h-screen text-white font-montserrat">
  <?php include 'navbar.php'; ?>
  
  <!-- Main Content Container -->
  <div class="pt-20 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Content Wrapper with proper overflow handling -->
      <div class="bg-dark-800/40 backdrop-blur-lg rounded-2xl shadow-2xl border border-dark-700/30 p-6 md:p-8 min-h-[calc(100vh-8rem)] overflow-hidden">
        <div class="max-w-full overflow-x-auto">
          <!-- Content from views will be inserted here -->