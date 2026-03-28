script = r"""
blade = open('/home/buja/projetos/barbearia-api/resources/views/filament/widgets/calendar-widget.blade.php', 'r').read()
# Save backup
open('/tmp/cal_blade_backup.php', 'w').write(blade)
print('backup saved:', len(blade), 'bytes')
"""
exec(compile(script, '<s>', 'exec'))
