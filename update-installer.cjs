const fs = require('fs');
let content = fs.readFileSync('deploy/installer.php', 'utf8');

// Replace Eezreb
content = content.replace(/Eezreb Matchmaking/g, 'PropOS Platform');
content = content.replace(/Eezreb/g, 'PropOS');

// Add cache:clear right before package:clear
content = content.replace(
    /\/\/ Clear package cache explicitly/,
    '// Clear application object cache\\n        run_artisan(\'cache:clear\');\\n\\n        // Clear package cache explicitly'
);

fs.writeFileSync('deploy/installer.php', content);
console.log('Updated installer.php');
