-- Host: localhost
-- Generation Time: May 23, 2018 at 10:03 AM
-- Server version: 10.1.32-MariaDB
-- PHP Version: 7.2.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `synet`
--

-- --------------------------------------------------------

--
-- Table structure for table `mikrotik`
--

CREATE TABLE `mikrotik` (
  `id` int(11) NOT NULL,
  `id_contrato` int(11) NOT NULL,
  `usuario` varchar(64) NOT NULL,
  `senha` varchar(128) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Login para equipamento se conectar no servidor.';


-- --------------------------------------------------------

--
-- Table structure for table `mikrotik_historico_acesso`
--

CREATE TABLE `mikrotik_historico_acesso` (
  `id` int(11) NOT NULL,
  `contrato_id` int(11) NOT NULL COMMENT 'relaciona com tabela contrato - opicional',
  `data_registro` datetime NOT NULL,
  `user` varchar(32) NOT NULL,
  `passwd` varchar(32) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Registra o historico de usuario e senhas da tabela mikrotik.';


-- --------------------------------------------------------

--
-- Table structure for table `mikrotik_ip-arp`
--

CREATE TABLE `mikrotik_ip-arp` (
  `id` int(10) NOT NULL,
  `ip` varchar(128) NOT NULL,
  `mac` varchar(32) NOT NULL,
  `interface` varchar(256) NOT NULL,
  `server_id` int(10) NOT NULL,
  `published` varchar(5) NOT NULL,
  `disabled` varchar(5) NOT NULL,
  `comment` varchar(256) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='vincula mac a um ip no mikrotik';


-- --------------------------------------------------------

--
-- Table structure for table `mikrotik_ping`
--

CREATE TABLE `mikrotik_ping` (
  `id` int(11) NOT NULL,
  `serverid` int(6) NOT NULL,
  `contratoid` int(11) NOT NULL,
  `protocoloid` int(11) NOT NULL,
  `ip_client` varchar(32) NOT NULL,
  `size` varchar(32) NOT NULL,
  `time` varchar(32) NOT NULL,
  `ttl` varchar(32) NOT NULL,
  `date_time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='grava historico de pings feito no servidor mk via sistema.';


-- --------------------------------------------------------

--
-- Table structure for table `mikrotik_planos`
--

CREATE TABLE `mikrotik_planos` (
  `id` int(11) UNSIGNED NOT NULL,
  `plan_id` int(10) NOT NULL COMMENT 'relaciona com a tabela plano - opicional',
  `max_limit` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '70K/70K',
  `burst_limit` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0K/0K',
  `burst_threshold` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0K/0K',
  `burst_time` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0s/0s',
  `time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `priority` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '8/8'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='clone da tabela `radgroupreply` no banco radius';


-- --------------------------------------------------------

--
-- Table structure for table `servidor`
--

CREATE TABLE `servidor` (
  `id` int(11) NOT NULL,
  `empresa` int(11) NOT NULL,
  `nome_servidor` varchar(155) DEFAULT NULL,
  `servidor_tipo` int(6) NOT NULL COMMENT 'relaciona com a tabela servidor_tipo - opicional',
  `ip_servidor` varchar(45) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `autenticacao` varchar(255) NOT NULL,
  `timestamp` varchar(255) DEFAULT NULL,
  `id_torre` int(11) NOT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL COMMENT '1 = ativo - 0 = inativo'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `servidor_services`
--

CREATE TABLE `servidor_services` (
  `id` int(10) NOT NULL,
  `server_id` int(10) NOT NULL COMMENT 'ID do servidor',
  `service_status` tinyint(1) NOT NULL COMMENT 'ativo ou inativo',
  `service_name` enum('api','api-ssl','ftp','ssh','telnet','winbox','www','www-ssl') NOT NULL,
  `service_port` int(5) NOT NULL,
  `service_available_from` varchar(255) NOT NULL COMMENT 'IPs serao gravados separados por virgula',
  `service_certificate` text NOT NULL COMMENT 'armazena a chave ou o path para chave'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='guarda os servicos do servidor - usado para mikrotik apenas';





--
-- SAMPLE DATA
--

--
-- Dumping data for table `mikrotik`
--
INSERT INTO `mikrotik` (`id`, `id_contrato`, `usuario`, `senha`) VALUES
(1, 1804, 'AwQ3ytPrgECZuTy', '95f8bb484de09fcddb1faaf6f656674a'), 
(2, 4713, 'NJuWytPCLpCZuDB', '65e6e6bc1997c131728a3f414254456a'),
(3, 6052, 'nzIsDZCQ7Skdg27', '23c124e1d2d4138ca5993a80ec9bc301');

--
-- Dumping data for table `mikrotik_historico_acesso`
--
INSERT INTO `mikrotik_historico_acesso` (`id`, `contrato_id`, `data_registro`, `user`, `passwd`) VALUES
(1, 1275, '2017-02-12 10:23:45', 'e1a7sd3w8ui0t', '95f8bb484de09fcddb1faaf6f656674a'),
(2, 2240, '2017-02-15 09:34:50', 'kQPodH1hPdw4sn', '3f673385838685951ba6e53585136463'),
(3, 2438, '2017-03-25 11:13:45', '82ahbBp04BkTY', '5b18cebd7b40c24301c86267df36ed8d');

--
-- Dumping data for table `mikrotik_ip-arp`
-- column `comment` can be used to point the ID (Transceiver or Client)
--
INSERT INTO `mikrotik_ip-arp` (`id`, `ip`, `mac`, `interface`, `server_id`, `published`, `disabled`, `comment`) VALUES
(1, '10.1.211.2', '00:0e:2e:de:57:76', 'bridge1-clientes', 1, 'false', 'false', 'ID DO EQUIPAMENTO CASO DE CLIENT'),
(2, '11.11.11.6', '00:02:38:12:24:6b', 'Teste-Interface', 2, 'false', 'false', 'ID DO EQUIPAMENTO CASO DE TRANSCEIVER');

--
-- Dumping data for table `mikrotik_ping`
--
INSERT INTO `mikrotik_ping` (`id`, `serverid`, `contratoid`, `protocoloid`, `ip_client`, `size`, `time`, `ttl`, `date_time`) VALUES
(1, 1, 1804, 0, '10.0.30.2', '56', '524ms', '255', '2017-09-25 23:01:33'),
(2, 2, 4713, 0, '10.0.30.2', '56', '412ms', '255', '2017-09-27 23:23:50'),
(3, 3, 6052, 824581, '10.0.22.2', '56', '7ms', '255', '2017-10-05 01:34:23');

--
-- Dumping data for table `mikrotik_planos`
--
INSERT INTO `mikrotik_planos` (`id`, `plan_id`, `max_limit`, `burst_limit`, `burst_threshold`, `burst_time`, `time`, `priority`) VALUES
(1, 10, '250K/250K', '400K/750K', '300K/500K', '60', '0h-1d,sun,mon,tue,wed,thu,fri,sat', '7/7'),
(2, 20, '80K/200K', '240K/999K', '400K/600K', '60/60', '0h-1d,sun,mon,tue,wed,thu,fri,sat', '7/7'),
(3, 30, '512K/1024K', '1280K/2560K', '896K/1792K', '60/60', NULL, '7/7'),
(4, 40, '400K/1M', '500K/3M', '450K/2M', '60/60', NULL, '7/7');

--
-- Dumping data for table `servidor`
-- servidor_tipo 1 = Mikrotik, 2 = BFW, 3 = ???, ..
--
INSERT INTO `servidor` (`id`, `empresa`, `nome_servidor`, `servidor_tipo`, `ip_servidor`, `usuario`, `autenticacao`, `timestamp`, `id_torre`, `notas`, `ativo`) VALUES
(1, 1, 'NET-CITY1-MI62', 1, '192.168.100.25', 'userapi', 'u/ndjfhtyHehs22b5ghgy0Og==', '1462235907', 1, 'MK para testes', 1),
(2, 1, 'NET-CITY2-MI62', 1, '192.168.100.24', 'userapi', '4lfjghtuyrhdgo2HX/X5i0rE8MMbsN5XVCnngSSAVfg=', '1462208886', 2, 'Seu comentario aqui', 1),
(3, 1, 'NET-CITY3-MI62', 1, '192.168.100.23', 'userapi', 'ui5P+4gkyu+BJF2k47ecVg==', '1458530493', 3, 'Seu comentario aqui', 1);

--
-- Dumping data for table `servidor_services`
--
INSERT INTO `servidor_services` (`id`, `server_id`, `service_status`, `service_name`, `service_port`, `service_available_from`, `service_certificate`) VALUES
(1, 4, 1, 'api', 8728, '', ''),
(2, 4, 1, 'winbox', 8291, '192.168.1.1,192.168.2.1,192.168.3.1,192.168.4.1,192.168.0.0/24', ''),
(3, 4, 1, 'www', 8080, '192.168.1.1,192.168.2.1,192.168.3.1,192.168.4.1,192.168.0.0/24', ''),
(5, 4, 1, 'api-ssl', 8729, '', ''),
(6, 4, 1, 'ftp', 21, '', ''),
(7, 4, 1, 'ssh', 22, '', ''),
(8, 4, 1, 'telnet', 23, '', ''),
(9, 4, 1, 'www-ssl', 443, '', ''),
(10, 5, 1, 'api', 8728, '', ''),
(11, 5, 1, 'winbox', 8291, '192.168.1.1,192.168.2.1,192.168.3.1,192.168.4.1,192.168.0.0/24', ''),
(12, 5, 1, 'www', 8080, '192.168.1.1,192.168.2.1,192.168.3.1,192.168.4.1,192.168.0.0/24', ''),
(13, 5, 0, 'api-ssl', 0, '', ''),
(14, 5, 0, 'ftp', 0, '', ''),
(15, 5, 0, 'ssh', 0, '', ''),
(16, 5, 0, 'telnet', 0, '', ''),
(17, 5, 0, 'www-ssl', 0, '', '');



--
-- Change datetime to use current_timestamp in case the system 
-- not passing the current date as parameter.
--
ALTER TABLE `mikrotik_ping` 
  CHANGE `date_time` `date_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `mikrotik`
--
ALTER TABLE `mikrotik`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_1` (`id_contrato`);

--
-- Indexes for table `mikrotik_historico_acesso`
--
ALTER TABLE `mikrotik_historico_acesso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_1` (`contrato_id`);

--
-- Indexes for table `mikrotik_ip-arp`
--
ALTER TABLE `mikrotik_ip-arp`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mikrotik_ping`
--
ALTER TABLE `mikrotik_ping`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mikrotik_planos`
--
ALTER TABLE `mikrotik_planos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `servidor`
--
ALTER TABLE `servidor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `servidor_services`
--
ALTER TABLE `servidor_services`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mikrotik`
--
ALTER TABLE `mikrotik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `mikrotik_historico_acesso`
--
ALTER TABLE `mikrotik_historico_acesso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `mikrotik_ip-arp`
--
ALTER TABLE `mikrotik_ip-arp`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `mikrotik_ping`
--
ALTER TABLE `mikrotik_ping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `mikrotik_planos`
--
ALTER TABLE `mikrotik_planos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `servidor`
--
ALTER TABLE `servidor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `servidor_services`
--
ALTER TABLE `servidor_services`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
