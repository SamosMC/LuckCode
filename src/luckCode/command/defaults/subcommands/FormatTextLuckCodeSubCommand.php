<?php

declare(strict_types=1);

namespace luckCode\command\defaults\subcommands;

use luckCode\command\LuckSubCommand;
use luckCode\LuckCodePlugin;
use luckCode\utils\text\TextFormatter;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use function array_count_values;
use function array_map;
use function count;
use function implode;
use function str_replace;
use function strpos;

class FormatTextLuckCodeSubCommand extends LuckSubCommand {

    /** @return string */
    public function getName(): string {
        return 'format';
    }

    /** @return array */
    public function getAliases(): array {
        return [];
    }

    /** @return string */
    public function getUsage(): string {
        return '/lc format [seu texto para formatar]';
    }

    /** @return string */
    public function getDescription(): string {
        return 'Teste o centralizamento de texto';
    }

    /**
     * @param CommandSender $sender
     * @return bool
     */
    public function canExecute(CommandSender $sender): bool {
        return $sender->hasPermission(LuckCodePlugin::ADMIN_PERMISSION);
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $s, array $args) {
        $prefix = LuckCodePlugin::PREFIX;
        if (empty($args)) {
            $s->sendMessage($prefix . '§cAgumentos inválidos! Use ' . $this->getUsage());
        } else if (array_count_values(array_map('strlen', $args)) < 10) {
            $s->sendMessage($prefix . '§cNha, essa frase é bem curta :v');
        } else if (strpos(implode(' ', $args), '\n') == false) {
            $s->sendMessage($prefix . '§cSe lembre que sua frase precisa ter um §f\n §cpara pular de linha :v');
        } else {
            // Antes que reclame dos str_replace "lincados" eu devo dizer que não consegui fazer esse negocio funcionar colocando os parametros em array :v
            $s->sendMessage($prefix . "§aSeu texto centralizado: \n§8\n§f" . TextFormatter::center(str_replace('\n', "\n", str_replace('&', '§', implode(" ", $args)))) . "\n§8" . ($s instanceof ConsoleCommandSender ? "\n§7(O texto pode não se alinhar perfeitamente devido a fonte do terminal!)\n" : null));
        }
    }
}