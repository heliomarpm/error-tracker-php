<?php

namespace HeliomarPM\ErrorTracker;

enum Severity: string
{
  case CRITICAL = 'critical';     // Exceções fatais, banco fora do ar, syntax error
  case WARNING = 'warning';       // Uso de funções obsoletas (deprecated), variáveis vazias
  case NOTICE = 'notice';         // Avisos informativos leves
  case DEPRECATED = 'deprecated'; // Usos de funções obsoletas
  case INFO = 'info';             // Logs manuais para rastreio
}
