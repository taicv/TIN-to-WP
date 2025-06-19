import markdown
import os

def convert_md_to_html(md_file_path, html_file_path):
    with open(md_file_path, 'r', encoding='utf-8') as f:
        md_content = f.read()
    html_content = markdown.markdown(md_content)
    with open(html_file_path, 'w', encoding='utf-8') as f:
        f.write(html_content)

# Convert complete_documentation.md
convert_md_to_html('documentation-website/complete_documentation.md', 'documentation-website/complete_documentation.html')

# Convert INSTALLATION.md
convert_md_to_html('documentation-website/INSTALLATION.md', 'documentation-website/INSTALLATION.html')

# Convert README.md
convert_md_to_html('documentation-website/README.md', 'documentation-website/README.html')

# Create index.html
index_html_content = '''
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Website Generator Documentation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; background: #f4f4f4; color: #333; }
        .container { width: 80%; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        nav { background: #333; color: #fff; padding: 10px 0; text-align: center; border-radius: 8px 8px 0 0; }
        nav a { color: #fff; text-decoration: none; padding: 10px 20px; display: inline-block; }
        nav a:hover { background: #555; }
        h1, h2, h3, h4, h5, h6 { color: #0056b3; }
        pre { background: #eee; padding: 10px; border-radius: 5px; overflow-x: auto; }
        code { font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="complete_documentation.html">Complete Documentation</a>
            <a href="INSTALLATION.html">Installation Guide</a>
            <a href="README.html">README</a>
        </nav>
        <h1>Welcome to the WordPress Website Generator Documentation</h1>
        <p>Please select a document from the navigation above to get started.</p>
    </div>
</body>
</html>
'''
with open('documentation-website/index.html', 'w', encoding='utf-8') as f:
    f.write(index_html_content)


