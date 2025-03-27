<?php

namespace App\Core;

use \Exception;
use \DateTime;

class CertGen
{
    private $template = '';
    private $placeholders = [];
    private $templateSrc = '';
    private $tmpPath = '';
    private $templatesPath;
    // private $certsPath;

    public function __construct(string $template, string $templatesPath, string $certsPath) {
        $this->templatesPath = $templatesPath;
        $this->certsPath = $certsPath;

        $this->setTemplate($template);
    }

    public function setTemplate(string $template) {
        $this->template = $template;

        $templatePath = $this->templatesPath . $this->template;

        if (!file_exists($templatePath)) {
            throw new Exception('template file ' . $templatePath . ' not found');
        }

        $this->tmpPath = $this->copyTemplate($templatePath);
        $this->templateSrc = file_get_contents($this->tmpPath);
    }

    public function create(array $placeholders = []): string {
        $nextLineOffset = null;
        
        if (array_key_exists('name', $placeholders)) {
            $node = $this->getNode('name');
            
            if ($node) $this->replaceNode($node, $placeholders['name'], '50%');
        }

        if (array_key_exists('number', $placeholders)) {
            $node = $this->getNode('number');
            
            if ($node) $this->replaceNode($node, $placeholders['number'], '50%');
        }

        if (array_key_exists('gender', $placeholders)) {
            $node = $this->getNode('gender');

            if ($node) $this->replaceNode($node, $placeholders['gender'], '50%');
        }

        if (array_key_exists('coursename', $placeholders)) {
            $node = $this->getNode('coursename');

            if ($node) {
                $lines = $this->split(trim($placeholders['coursename']), 52);
                // if (count($lines) == 2 || count($lines) == 3) {
                //     $firstLine = trim($lines[0]);
                //     $secondLine = trim($lines[1]);
                //     $secondLineNode = $this->copyNode($node);
                    
                //     if (count($lines) == 3) {
                //         $thirdLine = trim($lines[3]);
                //         $thirdLineNode = $this->copyNode($node);
                //     }

                //     $this->replaceNode($node, $firstLine, '50%');
                //     $this->replaceNode($secondLineNode, $secondLine, '50%', $node['y'] + ($node['y'] * 0.05));

                //     if (count($lines) == 3) {
                //         $this->replaceNode($thirdLineNode, $thirdLine, '50%', $node['y'] + ($node['y'] * 0.1));
                //         $nextLineOffset = ($node['y'] * 0.06);
                //     }

                //     $nextLineOffset = ($node['y'] * 0.06);
                // } else {
                //     $this->replaceNode($node, $placeholders['coursename'], '50%');
                // }

                if (count($lines) > 1) {
                    $offsetY = 0.05;
                    $firstEl = $lines[0];
                    array_shift($lines);
    
                    foreach ($lines as $i => $line) {
                        $node = $this->copyNode($node);
                        $this->replaceNode($node, $line, '50%', $node['y'] + ($node['y'] * $offsetY));
                        $offsetY += 0.05;

                        $nextLineOffset += ($node['y'] * 0.06);
                    }
    
                    $node = $this->getNode('coursename');
                    $this->replaceNode($node, $firstEl, '50%');
                } else {
                    $this->replaceNode($node, $placeholders['coursename'], '50%');
                }
            }
        }

        if (array_key_exists('hours', $placeholders)) {
            $node = $this->getNode('hours');
            
            if ($node) $this->replaceNode($node, $placeholders['hours'], '50%', $node['y'] + $nextLineOffset);
        }

        if (array_key_exists('name_00', $placeholders)) {
            $node = $this->getNode('name_00');
            
            if ($node) $this->replaceNode($node, $placeholders['name_00'], '48%', null, 'end');
        }

        if (array_key_exists('name_01', $placeholders)) {
            $node = $this->getNode('name_01');
            
            if ($node) $this->replaceNode($node, $placeholders['name_01'], '50%');
        }

        if (array_key_exists('rank', $placeholders)) {
            $node = $this->getNode('rank');
            
            if ($node) $this->replaceNode($node, $placeholders['rank'], '48%', null, 'end');
        }

        if (array_key_exists('text', $placeholders)) {
            $node = $this->getNode('text');
            $offsetY = 0.05;

            $lines = $this->split(trim($placeholders['text']), 60);
            if (count($lines) > 1) {
                $firstEl = $lines[0];
                array_shift($lines);

                foreach ($lines as $i => $line) {
                    $node = $this->copyNode($node);
                    $this->replaceNode($node, $line, '15%', $node['y'] + ($node['y'] * $offsetY), 'start');
                    $offsetY += 0.05;
                }

                $node = $this->getNode('text');
                $this->replaceNode($node, $firstEl, '15%', null, 'start');
            } else {
                $this->replaceNode($node, $placeholders['text'], '15%', null, 'start');
            }
        }

        $date = new DateTime();
        $certfn = $placeholders['certid'] . '_' . md5(str_replace(' ', '_', $placeholders['name'])) . '.svg';
        $certpath = str_replace('.svg', '_f.pdf', $this->certsPath . $certfn);
        $certpath_compressed = str_replace('.svg', '.pdf', $this->certsPath . $certfn);

        if (!is_dir($this->certsPath))
            mkdir($this->certsPath);

        $tmp = '/tmp/' . $certfn;
        file_put_contents($tmp, $this->templateSrc);

        exec('/usr/local/bin/cairosvg ' . $tmp . ' -o ' . $certpath);

        $cmd = 'ghostscript -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/printer -dNOPAUSE -dQUIET -dBATCH -sOutputFile=' . $certpath_compressed . ' ' . $certpath;

        $r = exec($cmd, $output, $return);

        if ($return) {
            echo 'Execute error, ' . $return;
            exit();
        }

        unlink($this->tmpPath);
        unlink($tmp);

        return $certpath_compressed;
    }

    private function split(string $s, int $len): array {
        $splited = [];
        $words = preg_split('/\s/', $s);

        if (!$words) return $splited;

        $line = 0;
        foreach ($words as $word) {
            if ($line >= count($splited))
                array_push($splited, []);

            if (!is_array($splited[$line]))
                $splited[$line] = [];

            array_push($splited[$line], $word);

            if (mb_strlen(implode(' ', $splited[$line])) >= $len)
                $line++;

        }

        return array_map(function ($e) { return implode(' ', $e); }, $splited);
    }

    private function copyNode($node) {
        $copy = $node['node'] . $node['node'];

        $this->templateSrc = str_replace($node['node'], $copy, $this->templateSrc);

        return $node;
    }

    private function replaceNode(array $node, string $value, $x = null, $y = null, $align = null) {
        $nodeCopy = $node['node'];

        if ($x && $x == '50%') 
            $nodeCopy = str_replace('x="' . $node['x'] . '"', 'x="' . $x . '" style="text-anchor: middle"', $nodeCopy);
        
        if (!$align) $align = '';

        if ($x && $x != '50%')
            $nodeCopy = str_replace('x="' . $node['x'] . '"', 'x="' . $x . '" alignment-baseline="baseline" style="text-anchor: ' . $align . '"', $nodeCopy);

        if ($y)
            $nodeCopy = str_replace('y="' . $node['y'] . '"', 'y="' . $y . '"', $nodeCopy);

        $nodeCopy = str_replace($node['placeholder'], $value, $nodeCopy);

        $this->templateSrc = preg_replace('/' . preg_quote($node['node'], '/') . '/', $nodeCopy, $this->templateSrc, 1);
    }

    private function getNode(string $nodeName): array {
        $nodeName = '&lt;' . $nodeName . '&gt;';
        preg_match('/<text x="([0-9.]+)" y="([0-9.]+)"[^>]+>' . $nodeName . '<\/text>/', $this->templateSrc, $matches);

        if (count($matches) == 3)
            return ['node' => $matches[0], 'x' => $matches[1], 'y' => $matches[2], 'placeholder' => $nodeName];

        return [];
    }

    private function getTemplateWidth(string $template): float {
        preg_match('/viewBox="([0-9.]+)\s([0-9.]+)\s([0-9.]+)\s([0-9.]+)?"/', $template, $values);

        if (!$values) throw new Exception('invalid template');

        return $values[3];
    }

    private function copyTemplate(string $path): string {
        // rand tmp path
        $tmpPath = '/tmp/' . md5($this->template . time() * rand(0, time()));

        try {
            copy($path, $tmpPath);
        } catch (\Throwable $th) {
            throw new Exception('unable to copy file ' . $path . ' to ' . $tmpPath);
        }

        return $tmpPath;
    }
}