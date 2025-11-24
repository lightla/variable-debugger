<?php

namespace lightla\VariableDebugger\DebugStrategy\Html;

use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugPrintStrategy;

class VariableDebugPrintHtmlPrintStrategy implements VariableDebugPrintStrategy
{
    public function printFromTrace(
        VariableDebugConfig $config,
        array $backtrace,
        ...$vars
    ): void
    {
        $caller = $backtrace[0];
        $caller['file'] = $this->calculateFilePathWithoutProjectRootPath($config, $caller['file']);

        $file = htmlspecialchars($caller['file']);
        $line = htmlspecialchars($caller['line']);

        echo '<div style="background:#2d2d2d;color:#d4d4d4;padding:15px;margin:15px 0;border:1px solid #444;border-radius:5px;font-family:Consolas,Monaco,monospace;font-size:12px;z-index:99999;">';
        echo '<div style="border-bottom:1px solid #555;padding-bottom:10px;margin-bottom:10px;color:#9cdcfe;">';
        echo "<strong>📁/</strong>{$file}:{$line}<br>";
        echo '</div>';

        foreach ($vars as $var) {
            echo '<div style="margin:10px 0;padding:10px;border:1px solid #444;border-radius:3px;">';
            echo '<pre style="margin:0;font-family:inherit;white-space:pre-wrap;">';
            echo $this->formatVariable($config, $var);
            echo '</pre>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function formatVariable(
        VariableDebugConfig $config,
        $var,
        $depth = 0,
        $indent = ''
    ): string
    {
        $output = '';

        # Prevent infinite recursion
        $maxDepth = $config->resolveMaxDepthOrDefault();

        if ($depth >= $maxDepth) {
            return '<span style="color:#808080;">[Max Depth Reached]</span>';
        }

        if (is_array($var)) {
            $count = count($var);
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">array</span>(<span style="color:#b5cea8;">' . $count . '</span>) ';
            }

            if ($count === 0) {
                $output .= '<span style="color:#808080;">[]</span>';
            } else {
                $output .= '[<br>';
                $i = 0;

                $showFirstArrayElement = (
                    $config->resolveShowArrayModeOrDefault()->isShowFirstElement()
                    && $depth === 0
                );

                foreach ($var as $key => $value) {
                    $newIndent = $indent . '  ';
                    $output .= $newIndent;

                    if (is_string($key)) {
                        $output .= '<span style="color:#ce9178;">"' . htmlspecialchars($key) . '"</span>';
                    } else {
                        $output .= '<span style="color:#b5cea8;">' . $key . '</span>';
                    }

                    $output .= ' <span style="color:#d4d4d4;">=></span> ';
                    $output .= $this->formatVariable($config, $value, $depth + 1, $newIndent);

                    if ($i < $count - 1) {
                        $output .= '<span style="color:#d4d4d4;">,</span>';
                    }
                    $output .= '<br>';
                    $i++;

                    // Nếu SHOW_FIRST mode và đây là level đầu, chỉ show 1 phần tử
                    if ($showFirstArrayElement) {
                        if ($count > 1) {
                            $othersCount = $count - 1;
                            $output .= $newIndent . "<span style='color:#808080;'>... (and {$othersCount} others)</span><br>";
                        }
                        break;
                    }
                }
                $output .= $indent . ']';
            }
        } elseif (is_object($var)) {
            $reflection = new \ReflectionClass($var);
            $className = $reflection->getName();

            if ($config->getShowValueType()) {
                // Hiển thị như cũ: object(ClassName) {
                $output .= '<span style="color:#4ec9b0;">object</span>(<span style="color:#c586c0;">' . htmlspecialchars($className) . '</span>) {<br>';
            } else {
                // Đơn giản hóa
                if ($className === 'stdClass') {
//                    $output .= '{<br>';  // stdClass chỉ hiển thị {}
                    $output .= '<span style="color:#c586c0;">' . htmlspecialchars($className) . '</span> {<br>';
                } else {
                    $output .= '<span style="color:#c586c0;">' . htmlspecialchars($className) . '</span> {<br>';  // ClassName {}
                }
            }

            // Get all properties including inherited ones
            $allProperties = [];
            $currentClass = $reflection;
            while ($currentClass) {
                foreach ($currentClass->getProperties() as $prop) {
                    $propName = $prop->getName();
                    if (!isset($allProperties[$propName])) {
                        $allProperties[$propName] = $prop;
                    }
                }
                $currentClass = $currentClass->getParentClass();
            }

            // Get dynamic properties (for stdClass and other objects)
            $objectVars = get_object_vars($var);

            if (empty($allProperties) && empty($objectVars)) {
                $output .= $indent . '  <span style="color:#808080;"># No properties</span><br>';
            } else {
                // Show declared properties first
                foreach ($allProperties as $prop) {
                    $prop->setAccessible(true);

                    if ($config->getShowDetailAccessModifiers()) {
                        $visibility = $prop->isPrivate() ? 'private' : ($prop->isProtected() ? 'protected' : 'public');
                        $output .= $indent . '  <span style="color:#c586c0;">' . $visibility . '</span> ';
                        $output .= '<span style="color:#9cdcfe;">' . $prop->getName() . '</span>: ';
                    } else {
                        $visibility = $prop->isPrivate() ? '-' : ($prop->isProtected() ? '#' : '+');
                        $output .= $indent . '  <span style="color:#c586c0;">' . $visibility . '</span>';
                        $output .= '<span style="color:#9cdcfe;">' . $prop->getName() . '</span>: ';
                    }

                    try {
                        if (!$prop->isInitialized($var)) {
                            $output .= '<span style="color:#808080;">[uninitialized]</span>';
                        } else {
                            $propValue = $prop->getValue($var);
                            $output .= $this->formatVariable($config, $propValue, $depth + 1, $indent . '  ');
                        }
                    } catch (\Exception $e) {
                        $output .= '<span style="color:#ff6b6b;">Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
                    }

                    $output .= '<br>';
                }

                // Show dynamic properties (for stdClass)
                foreach ($objectVars as $propName => $propValue) {
                    // Skip if already shown as declared property
                    if (!isset($allProperties[$propName])) {
                        if ($config->getShowDetailAccessModifiers()) {
                            $output .= $indent . '  <span style="color:#c586c0;">public</span> ';
                            $output .= '<span style="color:#9cdcfe;">"' . htmlspecialchars($propName) . '"</span>: ';
                        } else {
                            $output .= $indent . '  <span style="color:#c586c0;">+</span>';
                            $output .= '<span style="color:#9cdcfe;">"' . htmlspecialchars($propName) . '"</span>: ';
                        }
                        $output .= $this->formatVariable($config, $propValue, $depth + 1, $indent . '  ');
                        $output .= '<br>';
                    }
                }
            }
            $output .= $indent . '}';
        } elseif (is_string($var)) {
            $len = strlen($var);
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">string</span>(<span style="color:#b5cea8;">' . $len . '</span>) ';
            }
            $output .= '<span style="color:#ce9178;">"' . htmlspecialchars($var) . '"</span>';
        } elseif (is_int($var)) {
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">int</span>(<span style="color:#b5cea8;">' . $var . '</span>)';
            } else {
                $output .= '<span style="color:#b5cea8;">' . $var . '</span>';
            }
        } elseif (is_float($var)) {
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">float</span>(<span style="color:#b5cea8;">' . $var . '</span>)';
            } else {
                $output .= '<span style="color:#b5cea8;">' . $var . '</span>';
            }
        } elseif (is_bool($var)) {
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">bool</span>(<span style="color:#569cd6;">' . ($var ? 'true' : 'false') . '</span>)';
            } else {
                $output .= '<span style="color:#569cd6;">' . ($var ? 'true' : 'false') . '</span>';
            }
        } elseif (is_null($var)) {
            $output .= '<span style="color:#569cd6;">null</span>';
        } else {
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">' . gettype($var) . '</span> ';
            }
            $output .= htmlspecialchars(print_r($var, true));
        }

        return $output;
    }

    /**
     * @param VariableDebugConfig $config
     * @param string $filePath
     * @return string
     */
    private function calculateFilePathWithoutProjectRootPath(
        VariableDebugConfig $config,
        string $filePath
    ): string
    {
        if ($config->getProjectRootPath()) {
            return str_replace($config->getProjectRootPath() . '/', '', $filePath);
        }

        return ltrim($filePath, '/');
    }
}