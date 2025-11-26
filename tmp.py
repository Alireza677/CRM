from pathlib import Path
import re
text = Path('routes/web.php').read_bytes()
needle = b"['title' =>"
pos = text.find(needle)
print(pos)
print(text[pos:pos+80])
