<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baixe nosso App | BarberEasy</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    {{-- Tailwind CSS via CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        amber: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-950 text-gray-100 font-sans antialiased selection:bg-amber-500 selection:text-white">

    <header class="fixed top-0 z-50 w-full border-b border-white/10 bg-gray-950/80 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <div class="flex items-center gap-2">
                <span class="text-2xl font-black tracking-tighter text-white">BarberEasy</span>
            </div>
            <div class="flex items-center gap-5">
                <a href="/admin/login" class="hidden text-sm font-semibold text-gray-300 hover:text-white sm:block">Painel do Dono</a>
                <a href="/admin/register" class="rounded-full bg-amber-500 px-5 py-2.5 text-sm font-bold text-gray-950 transition hover:bg-amber-400 shadow-lg shadow-amber-500/20">
                    Seja um Parceiro
                </a>
            </div>
        </div>
    </header>

<main class="relative overflow-hidden pt-32 pb-16 sm:pt-40 lg:pb-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-12 lg:gap-16 lg:items-center">
                
                <div class="lg:col-span-6 text-center lg:text-left">
                    <div class="mb-6 inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-4 py-1.5 text-sm font-semibold text-amber-400">
                        ✨ O App oficial para seus clientes
                    </div>
                    <h1 class="mb-6 text-5xl font-black tracking-tight text-white sm:text-7xl">
                        Seu estilo, na <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600">palma da mão.</span>
                    </h1>
                    <p class="mb-10 mx-auto max-w-2xl text-lg leading-relaxed text-gray-400 lg:mx-0">
                        Agende seus cortes, escolha seu profissional favorito e gerencie sua assinatura mensal direto pelo celular. Rápido, prático e sem filas.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="{{ asset('downloads/barbereasy-app.apk') }}" download="BarbeariaApp.apk" class="flex w-full sm:w-auto items-center justify-center gap-3 rounded-2xl bg-amber-500 px-8 py-4 text-sm font-bold text-gray-950 transition hover:bg-amber-400 shadow-lg shadow-amber-500/20">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0221 3.503C15.5398 8.2435 13.8427 7.7503 12 7.7503s-3.5398.4932-5.137 1.1998l-2.0221-3.503a.4158.4158 0 00-.5676-.1521.4156.4156 0 00-.1521.5676l1.9973 3.4592C2.6657 11.2335.2539 14.6548 0 18.665h24c-.2539-4.0102-2.6657-7.4315-6.1185-9.3436"/></svg>
                            Baixar para Android
                        </a>
                    </div>
                </div>

                <div class="mt-20 lg:mt-0 lg:col-span-6 flex justify-center relative">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-72 h-72 bg-amber-500/20 rounded-full blur-[80px]"></div>
                    
                    <div class="relative w-[280px] h-[580px] rounded-[3rem] border-[8px] border-gray-800 bg-gray-900 shadow-2xl flex items-center justify-center overflow-hidden ring-1 ring-white/10">
                        <div class="absolute top-0 w-32 h-6 bg-gray-800 rounded-b-2xl"></div> 
                        <img src="{{ asset('images/APPimg.jpeg') }}" alt="App Preview" class="w-full h-full object-cover rounded-[2.5rem]">
                    </div>
                </div>
            </div>
        </div>
    </main>

    <section class="py-24 bg-gray-900/50 border-t border-white/5">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid gap-8 md:grid-cols-3">
                
                <div class="rounded-3xl border border-white/5 bg-white/5 p-8 transition hover:bg-white/10 hover:border-white/10">
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Agendamento Fácil</h3>
                    <p class="text-gray-400 leading-relaxed">Escolha o serviço, o barbeiro, o dia e o horário ideal sem complicações nem esperas.</p>
                </div>

                <div class="rounded-3xl border border-white/5 bg-white/5 p-8 transition hover:bg-white/10 hover:border-white/10">
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Assinatura Mensal</h3>
                    <p class="text-gray-400 leading-relaxed">Tenha acesso a planos exclusivos e gerencie sua assinatura direto pelo cartão de crédito.</p>
                </div>

                <div class="rounded-3xl border border-white/5 bg-white/5 p-8 transition hover:bg-white/10 hover:border-white/10">
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Controle Total</h3>
                    <p class="text-gray-400 leading-relaxed">Acompanhe seus horários agendados, cancele ou reagende quando precisar com poucos toques.</p>
                </div>

            </div>
        </div>
    </section>

    <footer class="border-t border-white/10 bg-gray-950 py-10 text-center">
        <p class="text-sm font-semibold text-gray-500">&copy; {{ date('Y') }} BarberEasy. Todos os direitos reservados.</p>
    </footer>

</body>
</html>