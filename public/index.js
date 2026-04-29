const { exec } = require("child_process");
const path = require("path");

export default function handler(req, res) {
  // Path to your PHP file
  const phpFile = path.join(process.cwd(), "index.php");

  // Command to run PHP via the CLI
  exec(`php ${phpFile}`, (error, stdout, stderr) => {
    if (error) {
      res.status(500).send(`Error executing PHP: ${error.message}`);
      return;
    }
    if (stderr) {
      res.status(500).send(`PHP Error: ${stderr}`);
      return;
    }

    // Send the PHP output back to the browser
    res.setHeader("Content-Type", "text/html");
    res.status(200).send(stdout);
  });
}
