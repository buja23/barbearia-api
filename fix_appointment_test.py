path = '/home/buja/projetos/barbearia-api/tests/Feature/AppointmentTest.php'
with open(path, 'r') as f:
    content = f.read()
old = "'status' => 'cancelled',"
new = "'status' => 'canceled',"
count = content.count(old)
content = content.replace(old, new)
with open(path, 'w') as f:
    f.write(content)
print(f'Replaced {count} occurrence(s)')
