<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $barbershop->name }} | BarberEasy</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #0f172a;
            --card: #111827;
            --card-2: #1f2937;
            --text: #f9fafb;
            --muted: #cbd5e1;
            --accent: #f59e0b;
            --border: rgba(255, 255, 255, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #020617 0%, #111827 100%);
            color: var(--text);
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }

        .hero {
            background: rgba(17, 24, 39, 0.92);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(245, 158, 11, 0.16);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        h1 {
            margin: 16px 0 8px;
            font-size: 40px;
            line-height: 1.1;
        }

        .description {
            margin: 0 0 24px;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.7;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .card {
            background: rgba(31, 41, 55, 0.92);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #fbbf24;
        }

        .card p {
            margin: 0;
            color: var(--text);
            line-height: 1.6;
            word-break: break-word;
        }

        .empty {
            margin-top: 28px;
            padding: 18px 20px;
            border-radius: 14px;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.25);
            color: #fde68a;
        }

        .footer {
            margin-top: 28px;
            color: #94a3b8;
            font-size: 14px;
        }

        @media (max-width: 640px) {
            .container {
                padding: 20px 16px 40px;
            }

            .hero {
                padding: 24px;
            }

            h1 {
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <section class="hero">
            <span class="badge">Agendamento online</span>

            <h1>{{ $barbershop->name }}</h1>

            @if (!empty($barbershop->description))
                <p class="description">{{ $barbershop->description }}</p>
            @else
                <p class="description">Escolha sua barbearia e em breve finalize seu agendamento online pelo BarberEasy.</p>
            @endif

            <div class="grid">
                <article class="card">
                    <h2>Telefone</h2>
                    <p>{{ $barbershop->phone ?: 'Não informado' }}</p>
                </article>

                <article class="card">
                    <h2>Endereço</h2>
                    <p>{{ $barbershop->address ?: 'Não informado' }}</p>
                </article>

                <article class="card">
                    <h2>Slug</h2>
                    <p>{{ $barbershop->slug }}</p>
                </article>
            </div>

            <div class="empty">
                A página pública foi criada para eliminar o erro 500 de view não encontrada. Você já pode evoluir esta tela com serviços, barbeiros, horários e formulário de reserva.
            </div>

            <p class="footer">BarberEasy © {{ now()->year }}</p>
        </section>
    </main>
</body>
</html>
