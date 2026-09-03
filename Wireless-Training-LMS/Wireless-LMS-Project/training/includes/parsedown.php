<?php
/**
 * Lightweight Markdown parser for WTS LMS.
 * Handles: headings, bold, italic, tables, blockquotes, lists, code, hr.
 */
class Parsedown {
    private bool $safeMode = false;

    public function setSafeMode(bool $safe): self { $this->safeMode = $safe; return $this; }

    public function text(string $text): string {
        $text  = str_replace("\r\n", "\n", $text);
        $text  = str_replace("\r", "\n", $text);
        $lines = explode("\n", $text);
        $html  = '';
        $i     = 0;
        $total = count($lines);

        while ($i < $total) {
            $line = $lines[$i];

            // Fenced code block
            if (preg_match('/^```/', $line)) {
                $code = '';
                $i++;
                while ($i < $total && !preg_match('/^```/', $lines[$i])) {
                    $code .= htmlspecialchars($lines[$i], ENT_QUOTES) . "\n";
                    $i++;
                }
                $html .= "<pre><code>$code</code></pre>\n";
                $i++;
                continue;
            }

            // Headings
            if (preg_match('/^(#{1,6})\s+(.+)/', $line, $m)) {
                $lvl   = strlen($m[1]);
                $html .= "<h$lvl>" . $this->inline($m[2]) . "</h$lvl>\n";
                $i++;
                continue;
            }

            // HR
            if (preg_match('/^[-*_]{3,}\s*$/', $line)) {
                $html .= "<hr>\n";
                $i++;
                continue;
            }

            // Blockquote
            if (preg_match('/^>\s?(.*)/', $line, $m)) {
                $bq = '';
                while ($i < $total && preg_match('/^>\s?(.*)/', $lines[$i], $bm)) {
                    $bq .= $bm[1] . "\n";
                    $i++;
                }
                $html .= '<blockquote>' . $this->text(trim($bq)) . "</blockquote>\n";
                continue;
            }

            // Table
            if (isset($lines[$i+1]) && preg_match('/^\|?[-:| ]+\|?$/', $lines[$i+1])) {
                $headers = $this->tableRow($line);
                $i += 2; // skip separator
                $rows = '';
                while ($i < $total && str_contains($lines[$i], '|')) {
                    $rows .= '<tr>' . $this->tableRow($lines[$i], false) . "</tr>\n";
                    $i++;
                }
                $thead = '<thead><tr>' . implode('', array_map(fn($h) => "<th>$h</th>", $headers)) . "</tr></thead>\n";
                $html .= "<table>\n$thead<tbody>\n$rows</tbody></table>\n";
                continue;
            }

            // Unordered list
            if (preg_match('/^[-*+]\s+(.+)/', $line, $m)) {
                $html .= "<ul>\n";
                while ($i < $total && preg_match('/^[-*+]\s+(.+)/', $lines[$i], $lm)) {
                    $html .= '<li>' . $this->inline($lm[1]) . "</li>\n";
                    $i++;
                }
                $html .= "</ul>\n";
                continue;
            }

            // Ordered list
            if (preg_match('/^\d+\.\s+(.+)/', $line, $m)) {
                $html .= "<ol>\n";
                while ($i < $total && preg_match('/^\d+\.\s+(.+)/', $lines[$i], $lm)) {
                    $html .= '<li>' . $this->inline($lm[1]) . "</li>\n";
                    $i++;
                }
                $html .= "</ol>\n";
                continue;
            }

            // Blank line
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // Paragraph — collect until blank line or block element
            $para = '';
            while ($i < $total && trim($lines[$i]) !== '' &&
                   !preg_match('/^(#{1,6}\s|[-*+]\s|\d+\.\s|```|>|[-*_]{3,})/', $lines[$i])) {
                $para .= $lines[$i] . ' ';
                $i++;
            }
            if (trim($para) !== '') {
                $html .= '<p>' . $this->inline(trim($para)) . "</p>\n";
            }
        }

        return $html;
    }

    private function inline(string $text): string {
        // Escape HTML if safe mode
        if ($this->safeMode) {
            $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
        }

        // Code span
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

        // Bold + italic
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/',     '<strong>$1</strong>',          $text);
        $text = preg_replace('/\*(.+?)\*/',          '<em>$1</em>',                  $text);
        $text = preg_replace('/__(.+?)__/',           '<strong>$1</strong>',          $text);
        $text = preg_replace('/_(.+?)_/',             '<em>$1</em>',                  $text);

        // Strikethrough
        $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);

        // Links [text](url)
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function($m) {
            $url = htmlspecialchars($m[2], ENT_QUOTES);
            return '<a href="' . $url . '">' . $m[1] . '</a>';
        }, $text);

        // Line break
        $text = preg_replace('/  \n/', '<br>', $text);

        // Checkmarks (used in lessons)
        $text = str_replace('✓', '<strong style="color:var(--green)">✓</strong>', $text);

        return $text;
    }

    private function tableRow(string $line, bool $isHeader = true): array {
        $line = trim($line, '| ');
        $cells = array_map('trim', explode('|', $line));
        if ($isHeader) {
            return array_map(fn($c) => $this->inline($c), $cells);
        }
        return array_map(fn($c) => '<td>' . $this->inline($c) . '</td>', $cells);
    }
}
