# Documentação

Este repositório deve cobrir mas não limita-se a: Addres List, Bridge, Ether, Graph, IP-ARP, NAS, PING, Queue List, Sec Profile, WLAN,..
Este Repositório é parte do sistema Synet para provedores de internet.


# Tratamento de erros e exceções do Mikrotik
	basicamente o que sera necessario obter da api em caso de erros seriam os
	campos marcados em * e tratar baseado na tabela `MIKROTIK ERROR CATEGORY`

  	`*>>> !trap`
	`*>>> =category=1`
	`*>>> =message=input does not match any value of interface`

	*****************************************
	MIKROTIK ERROR CATEGORY 
	*****************************************
	0 - missing item or command
	1 - argument value failure
	2 - execution of command interrupted
	3 - scripting related failure
	4 - general failure
	5 - API related failure
	6 - TTY related failure
	7 - value generated with :return command
	*****************************************


# Interfaces
	Todos os scripts contidos nesse pacote devem executar operações no servidor 
	Mikrotik usando o metodo API. Nesse pacote não esta sendo disponibilizado 
	interfaces de interação direta com o usuario, no entanto essa interacao pode 
	ser feita atraves de uma requisição do tipo GET ou POST dependendo do script.


# Autenticação com o servidor
	Os dados de autenticação devem ser passados diretamente no script ou chamado 
	via consulta no banco de dados. 


# Banco de Dados
	`Create User, Grant Privilegies for that user, Create Database, Grant Privilegies for that database.`
	`CREATE USER 'synet'@'localhost' IDENTIFIED VIA mysql_native_password USING '***';`
	`GRANT ALL PRIVILEGES ON *.* TO 'synet'@'localhost' REQUIRE NONE WITH GRANT OPTION 
		MAX_QUERIES_PER_HOUR 0  MAX_CONNECTIONS_PER_HOUR 0  MAX_UPDATES_PER_HOUR 0  MAX_USER_CONNECTIONS 0;`
	`CREATE DATABASE IF NOT EXISTS `synet`;`
	`GRANT ALL PRIVILEGES ON `synet`.* TO 'synet'@'localhost';`
	`GRANT ALL PRIVILEGES ON `synet\_%`.* TO 'synet'@'localhost';`

	Importar arquivo `schema.sql` para o banco usando o comando:
	`mysql -h <ip> -u <user> -p<passwd> <database> < /path/schema.sql`


# Dados para teste
	Cada script possui seu proprio array com dados, no momento os testes estão sendo 
	feitos apenas com arrays para efeito de demonstração.


# Servidor Mikrotik para teste
	Para poder testar o script eu sugiro ter o seu proprio servidor mikrotik.
	Caso não possua um servidor para testes, acompanhe a tread no site do mikrotik 
	(https://forum.mikrotik.com/viewtopic.php?f=2&t=104266&sid=67bcf476eae0590d962662349e343a05&start=400), 
	alguem disponibilizou um servidor de testes porem não estou certo que esteja online ainda.


# Development Roadmap (TODO LIST)
- [ ] Gerenciar grupos de usuarios do mikrotik.
- [ ] Gerenciar usuarios do mikrotik.
- [ ] Gerenciar os serviço do sistema (IP>Services).
- [ ] Cadastro de servidor Hotspot.
- [ ] Cadastro de servidor PPPoE.
- [ ] Automação Geral do servidor mikrotik.
- [ ] Visualização geral do servidor.
- [ ] Visualização geral do servidor NAS (repetidoras).


# Referencias
	http://wiki.mikrotik.com/wiki/API_PHP_class#Class
	https://forum.mikrotik.com/viewtopic.php?f=2&t=104266&sid=67bcf476eae0590d962662349e343a05&start=400
