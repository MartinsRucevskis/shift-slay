<?php

namespace App\Shift\Shifts;

use App\Shift\Rector\Shifter\BreakingChangeFinder;

class UpdateProject implements BaseShift
{
    public function run(string $directory): void
    {
        $this->findChanges();
    }

    private function findChanges()
    {
//        $vendorFolder = scandir(base_path('/vendor'));
//        $vendorFolders = array_filter($vendorFolder, function ($path) use ($vendorFolder) {
//           return is_dir($vendorFolder . '/' . $path) &&
//               !in_array($path, ['.', '..', 'bin', 'composer']);
//        });
//        $vendorPackageDirectories = [];
//        foreach ($vendorFolders as $packagesFolder) {
//            $packagesInDir = scandir($vendorFolder . '/' . $packagesFolder);
//            $packagesInDir = array_filter($packagesInDir, function ($path) use ($packagesInDir) {
//               return is_dir($packagesInDir . '/' . $path) && !in_array($path, ['.', '..']);
//            });
//            foreach ($packagesInDir as $package) {
//                $vendorPackageDirectories[] = $vendorFolder . '/' . $packagesFolder;
//            }
//        }
        $banda = (new BreakingChangeFinder())->findBreakingChanges(
            '/home/martins/projects/shift-slay/monolog2',
            '/home/martins/projects/shift-slay/monolog3'
        );
    }
}
