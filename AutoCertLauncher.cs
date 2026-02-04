using System;
using System.Diagnostics;
using System.IO;
using System.Runtime.InteropServices;

class AutoCertLauncher
{
    [DllImport("kernel32.dll")]
    private static extern IntPtr GetConsoleWindow();

    [DllImport("user32.dll")]
    private static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);

    private const int SW_HIDE = 0;
    private const int SW_SHOW = 5;

    static int Main(string[] args)
    {
        try
        {
            string appDir = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ApplicationData), "AutoCert");
            string batchFile = Path.GetDirectoryName(Process.GetCurrentProcess().MainModule.FileName) + "\\AutoCert.bat";

            if (!File.Exists(batchFile))
            {
                Console.WriteLine("Error: AutoCert.bat not found!");
                Console.WriteLine("Press any key to exit...");
                Console.ReadKey();
                return 1;
            }

            // Hide console window if requested
            // var handle = GetConsoleWindow();
            // ShowWindow(handle, SW_HIDE);

            // Run the batch file
            ProcessStartInfo psi = new ProcessStartInfo
            {
                FileName = "cmd.exe",
                Arguments = $"/c \"{batchFile}\"",
                UseShellExecute = true,
                CreateNoWindow = false,
                WorkingDirectory = Path.GetDirectoryName(batchFile)
            };

            using (Process process = Process.Start(psi))
            {
                process.WaitForExit();
                return process.ExitCode;
            }
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Error: {ex.Message}");
            Console.WriteLine("Press any key to exit...");
            Console.ReadKey();
            return 1;
        }
    }
}
