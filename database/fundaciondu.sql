-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-10-2026 a las 04:35:42
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `fundaciondu`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `congresos`
--

CREATE TABLE `congresos` (
  `id` int(10) UNSIGNED NOT NULL,
  `contenido` longtext NOT NULL,
  `publicado` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `congresos`
--

INSERT INTO `congresos` (`id`, `contenido`, `publicado`, `updated_at`) VALUES
(1, '{\"title\":\"I Congreso Internacional CIDIA\",\"subtitle\":\"Ciencia de Datos e Inteligencia Artificial\",\"intro\":\"Conoce a los ponentes, tarifas y medios de pago consignados para el congreso.\",\"summary\":\"Es un espacio internacional de encuentro académico y profesional que reunirá a expertos, investigadores y estudiantes para compartir conocimientos, experiencias y avances en ciencia de datos e inteligencia artificial, impulsando la innovación y la colaboración global.\",\"logo\":\"assets/images/congreso/logo-cdia.jpg\",\"date\":\"24, 25 y 26 de Noviembre\",\"time\":\"\",\"modality\":\"Virtual\",\"venue\":\"Virtual\",\"registration_url\":\"https://docs.google.com/forms/d/1ou2VGKG6iCtt8c9c5iTrHmhAZb-1dJqsitjoOSIzhfQ/viewform?ts=6ac52dce&edit_requested=true&fbzx=-9218151107962792597\",\"registration_qr\":\"/fundaciondu/public/uploads/58a03cc8489658d81ef80219cc3e3f7b.png\",\"contact_email\":\"congresocidia@fundaciondu.org\",\"payment\":{\"bank\":\"Banco de Crédito del Perú (BCP)\",\"holder\":\"Fundación para el Desarrollo Universitario UNTELS\",\"account\":\"194-6929357-0-03\",\"cci\":\"00219400692935700397\",\"wallet_name\":\"Yape\",\"yape\":\"940 404 384\",\"yape_holder\":\"Luzbeth Karin Navarrete Leal\",\"note\":\"\",\"yape_enabled\":true},\"certifications\":[\"<p>La tarifa incluye certificación de Perú.</p><p>Certificación doble (Perú–China): S/ 50.00 adicionales.</p><p>Certificación triple (Perú–China–Brasil): S/ 100.00 adicionales.</p>\"],\"sponsors\":[{\"name\":\"Cámara de Comercio de Jiangsu (China) del Perú\",\"logo\":\"/fundaciondu/public/uploads/4f8b8d30c336f801229d3ec2e580650f.png\",\"description\":\"\"}],\"countries\":[],\"fees\":[{\"audience\":\"Público en general\",\"amount\":\"S/ 200.00\"},{\"audience\":\"Estudiantes en general\",\"amount\":\"S/ 100.00\"},{\"audience\":\"Personal UNTELS\",\"amount\":\"S/ 100.00\"},{\"audience\":\"Estudiantes UNTELS\",\"amount\":\"S/ 50.00\"}],\"speakers\":[{\"name\":\"AGUILAR GUTIERREZ LUIS ANTONIO\",\"institution\":\"No especificada en el documento\",\"country\":\"Chile\",\"photo\":\"\",\"links\":[\"https://dina.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do;jsessionid=69464bf279c103a1a938c9384659?id_investigador=67956\"],\"biography\":\"<p>Especialista en el área de Inteligencia Artificial, concretamente en Machine Learning y Deep Learning. En estas áreas he desarrollado diversas aplicaciones usando aprendizaje supervisado como no supervisado. Actualmente miembro del grupo de investigacionKapAITech: kapaitech.com/</p>\",\"flag\":\"\"},{\"name\":\"YOSBI GOLLES PAICO\",\"institution\":\"Universidad Peruana Cayetano Heredia\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/yosbi-golles-paico.jpg\",\"links\":[],\"biography\":\"<p>Matemático egresado de la UNP. Ha finalizado la maestría en Matemática Aplicada de la UNP. Con formación en modelamiento matemático, optimización, machine learning y cómputo científico. Actualmente es investigador en la Universidad Peruana Cayetano Heredia, donde desarrolla investigación en reconstrucción holográfica sin lentes para microscopía, mediante propagación numérica de ondas y recuperación iterativa de fase. Previamente fue investigador en la Universidad Nacional de Trujillo, trabajando en investigación de operaciones, programación entera mixta y modelos de localización y ruteo de vehículos aplicados a problemas reales de planificación logística. Su trabajo integra modelos matemáticos y físicos con herramientas computacionales. Ha obtenido el primer lugar en el VII Concurso de Iniciación Científica “Matemática y sus Aplicaciones” (X CIMAC, 2021).</p>\",\"flag\":\"\"},{\"name\":\"LENIN HERRERA PADILLA\",\"institution\":\"Universidad Nacional de Ingeniería\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/lenin-herrera-padilla.jpg\",\"links\":[],\"biography\":\"<p>Lic. en Matemática por la Universidad Nacional de Piura y estudiante de la Maestría en Inteligencia Artificial en la Universidad Nacional de Ingeniería, con sólida base en modelado matemático, ecuaciones diferenciales y optimización aplicada al desarrollo de soluciones de inteligencia artificial. Mi línea de investigación se centra en las Redes Neuronales Informadas por la Física (PINNs) para la resolución de problemas directos e inversos, integrando el conocimiento físico con modelos de deeplearning. Cuenta con experiencia en el desarrollo de proyectos de machine learning para regresión, clasificación y agentes inteligentes, utilizando Python, TensorFlow, DeepXDE, scikit-learn, pandas y entornos como Google Colab y AWS.</p>\",\"flag\":\"\"},{\"name\":\"JEYNER SUAREZ GUERRERO\",\"institution\":\"Universidad Nacional de Piura\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/jeyner-suarez-guerrero.jpg\",\"links\":[],\"biography\":\"<p>Licenciado en Matemática por la Universidad Nacional de Piura, con experiencia en ciencia de datos, modelado estadístico y aprendizaje automático. Domina redes bayesianas, series de tiempo, DEA, árboles de decisión, SVM y redes neuronales. Se ha desempeñado como Ingeniero de Datos en el Vicerrectorado de Investigación de la UNP y como docente en análisis de datos en CAMU–Costa Rica (big data, machine learning y deeplearning con Python). Ha sido ponente en el II y III Congreso Internacional de Machine Learning, Lógica Difusa y sus Aplicaciones Prácticas y en la XIII Conferencia Académica del Programa de Intercambio Educativo de la Universidad del Pacífico. Maneja Python, R, SQL, C++ y MATLAB.</p>\",\"flag\":\"\"},{\"name\":\"Dr. EDMUNDO RUBEN VERGARA MORENO\",\"institution\":\"No especificada en el documento\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/edmundo-vergara-moreno.jpg\",\"links\":[\"https://dina.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=1239\"],\"biography\":\"<p>Doctor en Ciencias Matemáticas (Granada-España, 1999), con estudios de Pregrado y maestría en la Universidad Nacional de Trujillo. Profesor visitante en la Universidad de Loja Ecuador (1994). Investigador visitante en las universidades de: Las Palmas de Gran Canaria (1996), Universidad de Granada (2002, 2012), Universidad Estadual Pulista (2002, 2005, 2013) y Universidad Saarlandes (2007). se dedica al área de la optimización Lineal clásica, difusa y los métodos heurísticos y sus aplicaciones.</p>\",\"flag\":\"\"},{\"name\":\"Dr. RAUL ALFREDO SANCHEZ ANCAJIMA\",\"institution\":\"Universidad Nacional de Tumbes\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/raul-sanchez-ancajima.jpg\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=18514\"],\"biography\":\"<p>Doctor en Matemática por la Escuela de Posgrado de la Universidad Nacional de Trujillo. Docente investigador de la Universidad Nacional de Tumbes (Docente Principal). Mi interés para investigación es en el área de inteligencia artificial, minería de datos, educación regresión logística, cálculo fraccionario. Soy Licenciado en Matemática y Maestro en Ciencias con mención en Matemática Aplicada por la Universidad Nacional de Piura, Responsable, ordenado y con condiciones para trabajar en equipo.</p>\",\"flag\":\"\"},{\"name\":\"Dr. MAXIMILIANO EPIFANIOASIS LOPEZ\",\"institution\":\"UNASAM\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/maximiliano-lopez.jpg\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=29077\"],\"biography\":\"<p>Doctor en Ciencia e Ingeniería de la Computación y se desempeña como docente de Matemáticas y Computación en la Facultad de Ciencias, Departamento Académico de Matemática, de la Universidad Nacional Santiago Antúnez de Mayolo (UNASAM), en la ciudad de Huaraz. Bachiller en Matemática y la Licenciatura en Matemática, con mención en Investigación de Operaciones e Informática. Además Bachiller y el título profesional en Ingeniería Informática y de Sistemas. Cuenta con una Maestría en Computación e Informática por la UNASAM y una Maestría en Matemática. Ha complementado su formación académica con una Diplomatura en Inteligencia Artificial en la Pontificia Universidad Católica de Chile. Actualmente, es coordinador del Grupo de Investigación en Matemática Difusa y Aprendizaje de Máquina (MATDIAMA). Sus líneas de investigación de interés incluyen la Inteligencia Artificial, la Lógica Difusa, el Aprendizaje de Máquina, la Computación Gráfica, la Optimización y los Métodos Numéricos.</p>\",\"flag\":\"\"},{\"name\":\"Dr. NILTON ARCE FERNANDEZ\",\"institution\":\"Universidad Nacional de Jaén\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/nilton-arce-fernandez.jpg\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=93474\"],\"biography\":\"<p>Licenciado en Matemáticas, Magister en Administración con mención en Gerencia Empresarial, Magister en Ciencias con mención en Matemática Aplicada y Doctor en Ciencias Matemáticas por la Universidad Nacional de Piura. Investigador en temas de Modelado Matemático, Inteligencia Artificial e Integración de las TIC en la Enseñanza de las Ciencias Básicas. Docente nombrado a tiempo completo en la Universidad Nacional de Jaén. Actualmente calificado en el Nivel III en RENACYT.</p>\",\"flag\":\"\"},{\"name\":\"Dr. JOSE ALFREDOHERRERA QUISPE\",\"institution\":\"UNMSM\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/jose-herrera-quispe.jpg\",\"links\":[\"https://dina.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=19\"],\"biography\":\"<p>Doctor en Ciencia de la Computación especializado en Inteligencia Artificial, Minería de Datos y Computación Ambiental. Obtuvo su doctorado en la UNSA con Beca CONCYTEC y pasantía de formación en el LMTG  Universidad Paul Sabatier (Francia). Concluyo el Mastering in Innovation &amp; DesignThinking en el MIT como parte del programa de profesionalización. Fue Director de Información y Gestión del Conocimiento en INAIGEM (MINAM), donde impulsó laboratorios de visión computacional, IA para detección de avalanchas, IoT en subcuencas de riesgo y análisis social. Lideró el modelo de gestión del conocimiento institucional, el Geoportal de mapas de riesgo y un inventario 3D de cordilleras, con proyectos de robótica para glaciares. En la UNSA, fue Director de Investigación, creó la marca UNSA-Investiga con CONCYTEC, dirigió la maestría en Informática financiada por Cienciactiva y fortaleció la producción científica mediante fondos canon. Profesor en la primera escuela acreditada por ICACIT en Computación. Actualmente es Profesor Principal y Director de la Escuela de Ciencia de la Computación en la UNMSM, liderando iniciativas académicas, científicas y de responsabilidad social.</p>\",\"flag\":\"\"},{\"name\":\"Dr. MANUEL ABELARDO ALCÁNTARA RAMÍREZ\",\"institution\":\"UNTELS\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/manuel-alcantara-ramirez.jpg\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=105356\"],\"biography\":\"<p>Doctor en Ingeniería de Sistemas, Doctor en Matemáticas, Magister en Ingeniería de Sistemas, Ingeniero de Sistemas y Licenciado enMatemáticas, Investigador Renacyt, Responsable y titular de “CIDIA - Grupo de Investigación en Ciencia de Datos e Inteligencia Artificial” de la UNTELS, Miembro del grupo Internacional de Investigación CDIA. Director de la Escuela Profesional de Ingeniería en Ciencia de Datos e Inteligencia artificial de la Universidad Nacional Tecnológica de Lima Sur (UNTELS). Docente en el pre y Posgrado de la Universidad Nacional del Callao (UNAC). Docente Principal en la UNTELS.</p>\",\"flag\":\"\"},{\"name\":\"Dr. FLABIO ALFONSO GUTIERREZ SEGURA\",\"institution\":\"Universidad Nacional de Piura\",\"country\":\"Perú\",\"photo\":\"assets/images/congreso/flabio-gutierrez-segura.jpg\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=21770\",\"https://www.researchgate.net/profile/Flabio-Gutierrez-Segura\"],\"biography\":\"<p>Doctor en Matemática en la Univ. Nacional de Trujillo (Perú), Estudios Doctorado en Informática - Univ. Politécnica de Valencia (España). Magister en Matemática Aplicada - Universidad Nacional de Piura (Perú), Magister en Ciencias de la computación por la Universidad de Cantabria (España). Licenciado en Matemática UNT-Trujillo. Docente del Dpto. de Matemática - Universidad Nacional de Piura. Investigador en Inteligencia Artificial (Sistemas inteligentes, Lógica difusa, Optimización Difusa, Machine Learning, Redes Neuronales, Algoritmos Genéticos).</p>\",\"flag\":\"\"},{\"name\":\"RODRIGO RAMOS GUIMARÃES\",\"institution\":\"UFRJ\",\"country\":\"Brasil\",\"photo\":\"assets/images/congreso/rodrigo-ramos-guimaraes.jpg\",\"links\":[\"http://lattes.cnpq.br/7842475190791460\",\"https://orcid.org/0009-0009-5515-9485\"],\"biography\":\"<p>Graduado en Análisis y Desarrollo de Sistemas por la Universidad Estácio de Sá (2010) y realizó estudios de posgrado en Gobernanza de TI y Desarrollo Móvil (MIT). Posee una sólida experiencia en el campo de la Informática, con especialización en Arquitectura de Sistemas Informáticos, trabajando en proyectos de desarrollo de software, integración de sistemas, infraestructura y soluciones corporativas. Actualmente cursa una maestría en el Programa de Posgrado en Informática (PPGI) de la Universidad Federal de Río de Janeiro, donde desarrolla una investigación centrada en tecnología, arquitectura de sistemas e innovación aplicada.</p>\",\"flag\":\"\"},{\"name\":\"MARY MANHÃES\",\"institution\":\"UFF\",\"country\":\"Brasil\",\"photo\":\"assets/images/congreso/mary-manhaes.jpg\",\"links\":[\"http://lattes.cnpq.br/6973300839713560\",\"https://orcid.org/0000-0002-0605-3117\"],\"biography\":\"<p>Profesora de Informática en la Universidad Federal Fluminense (UFF) desde 2017. Profesora coordinadora de cursos en el consorcio CEDERJ/UENF desde 2017. Doctora en Ingeniería de Sistemas yComputación PESC/COPPE/UFRJ (2015), título de la tesis \\\"Predicción del rendimiento académico de estudiantes de pregrado mediante minería de datos educativos\\\". Maestría en Informática por la VrijeUniversiteitBrussel (1999) Programa Capes/Cofecub EMOOSE (Máster Europeo en Ciencias de la Ingeniería de Software Orientada a Objetos). Licenciada en Matemáticas por la Facultad de Filosofía de Campos (1997). Experiencia profesional como profesora universitaria desde 1993. Experiencia en educación a distancia en el consorcio CEDERJ desde 2000. Especialización en Informática, centrándose en los siguientes temas: Ciencia de Datos, Minería de Datos, Inteligencia Artificial, Estadística, Gestión del Conocimiento, Ingeniería de Software, Educación a Distancia, Informática en la Educación, Software Educativo y Bases de Datos.</p>\",\"flag\":\"\"},{\"name\":\"FELIPE RODRIGUES CALÉ\",\"institution\":\"UFRJ\",\"country\":\"Brasil\",\"photo\":\"assets/images/congreso/felipe-rodrigues-cale.jpg\",\"links\":[\"http://lattes.cnpq.br/5368631771058240\"],\"biography\":\"<p>Profesional con más de 10 años de experiencia en desarrollo de software, con una sólida trayectoria en entornos ágiles, proyectos a gran escala y tecnologías emergentes. Desde 2020, he liderado equipos técnicos en soluciones web y móviles, centrándome en la escalabilidad, la automatización de pruebas y la innovación, incluyendo iniciativas con blockchain, IoT y computación en la nube. Gran capacidad para garantizar entregables de alto valor añadido y calidad constante.Ingeniero de Software y Líder Técnico | Java, Spring Boot, Angular, Cloud y Web3 | Estudiante de Maestría en Informática en la UFRJ.</p>\",\"flag\":\"\"},{\"name\":\"ALESSANDRA CARBONEL\",\"institution\":\"UFRJ\",\"country\":\"Brasil\",\"photo\":\"assets/images/congreso/alessandra-carbonel.jpg\",\"links\":[\"http://lattes.cnpq.br/9417222739212083\"],\"biography\":\"<p>Licenciado en Meteorología por la Universidad Federal de Río de Janeiro (UFRJ) y cuento con experiencia en investigación científica en diversas áreas de meteorología, climatología y astrofísica. Experiencia en el procesamiento y la verificación de la consistencia de grandes bases de datos ambientales mediante diversos lenguajes de programación científica, posee conocimientos computacionales que me permiten trabajar con diferentes tipos de datos, además de crear mapas que facilitan el análisis y la predicción meteorológica y climática para diversas aplicaciones.Experiencia científica en las áreas de meteorología, climatología y astrofísica, con aplicaciones de técnicas estadísticas a datos ambientales y análisis de datos, principalmente datos meteorológicos y de calidad del aire obtenidos a través de redes de monitoreo con sensores in situ y remotos disponibles, como datos atmosféricos obtenidos de radiosondas e instrumentos como SODAR y LIDAR. En el campo de la astrofísica, experiencia en el procesamiento y la manipulación de datos obtenidos a través del telescopio espacial Chandra, los telescopios ópticos ESO/MPI, ESO/VLT y Cassini, y el interferómetro del radiotelescopio VLA.</p>\",\"flag\":\"\"},{\"name\":\"RONILSON RODRIGUES PINHO\",\"institution\":\"CEFET/RJ y UFRJ\",\"country\":\"Brasil\",\"photo\":\"assets/images/congreso/ronilson-rodrigues-pinho.jpg\",\"links\":[\"https://www.linkedin.com/in/ronilson-pinho-1750a336/\",\"https://orcid.org/0009-0008-0852-2803\"],\"biography\":\"<p>Posee un título en Tecnología de Procesamiento de Datos por la Facultad Integrada Simonsen (1995), un posgrado en Análisis y Diseño de Sistemas por la PUC-RJ y una maestría en Modelado Matemático y Computacional por la UFRRJ. Actualmente es candidato a doctorado (desde 2019) en Ciencias de la Computación en la UFRJ. Es profesor titular de EBTT en el CEFET/RJ, Campus María da Graça, donde imparte clases en el curso de Técnico en Automatización Industrial y en el programa de pregrado de Sistemas de Información.Profesor na Cefet-RJ.</p>\",\"flag\":\"\"},{\"name\":\"MARIANA GONÇALVES DA COSTA\",\"institution\":\"UFRJ\",\"country\":\"Brasil\",\"photo\":\"assets/images/congreso/mariana-goncalves-da-costa.jpg\",\"links\":[\"http://lattes.cnpq.br/1485694235655537\",\"https://orcid.org/0000-0002-8088-0794\"],\"biography\":\"<p>Licenciada en Literatura Portuguesa e Inglesa por la Universidad Federal de Río de Janeiro (UFRJ), actualmente cursa una Maestría en Metodología y Técnicas de Computación en PPGI/UFRJ, donde desarrolla investigación en la intersección de la lingüística y la informática, centrándose en el análisis e identificación de expresiones multipalabra. Colabora con el portal inCorpora, en el área de Procesamiento del Lenguaje Natural, y con el proyecto de extensión ReHDLinguistics. Entre 2018 y 2022, participó en el proyecto PREDICAR, coordinado por la Prof. Dra. Marcia Machado Vieira (UFRJ), basado en la Gramática de Construcciones Basada en el Uso. Actualmente, forma parte del sector de investigación del proyecto de extensión Minerv@sDigitais, cuyo objetivo es fomentar la participación femenina en las áreas STEM. Cuenta con experiencia en traducción, edición técnica y enseñanza de idiomas. Sus principales intereses son el Procesamiento del Lenguaje Natural, las Humanidades Digitales, la Ciencia de Datos, la Edición y la Traducción.</p>\",\"flag\":\"\"},{\"name\":\"Dr. RENATO CERCEAU\",\"institution\":\"UFRRJ\",\"country\":\"Brasil\",\"photo\":\"assets/images/congreso/renato-cerceau.jpg\",\"links\":[\"http://lattes.cnpq.br/1652132080233697\",\"https://orcid.org/0000-0003-3953-4715\"],\"biography\":\"<p>Es licenciado en Medicina (UFF, 1997), licenciado en Derecho (UFF, 2022), máster en Ingeniería Biomédica (COPPE/UFRJ, 2004) y doctor en Ingeniería de Sistemas e Informática (COPPE/UFRJ, 2018). Actualmente, investigador en el Instituto de Investigación Alda y en la Universidad Federal Rural de Río de Janeiro (UFRRJ), socio de la empresa de tecnología sanitaria TervisSaúde y especialista en regulación de la salud complementaria en la Agencia Nacional de Salud Complementaria (ANS). Cuento con experiencia en docencia, investigación, innovación y extensión en las áreas de Medicina y Derecho, centrándome principalmente en los siguientes temas: salud colectiva/salud pública, planificación sanitaria, salud complementaria (seguro médico) y Ciencia de Datos/Inteligencia Artificial.</p>\",\"flag\":\"\"},{\"name\":\"Dr. JORGE JUAN ZAVALETA GAVIDIA\",\"institution\":\"UFRRJ\",\"country\":\"Brasil\",\"photo\":\"assets/images/congreso/jorge-zavaleta-gavidia.jpg\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=110178\",\"http://lattes.cnpq.br/5989368756609995\"],\"biography\":\"<p>Investigador con postdoctorado en Métodos de Aprendizaje Automático Aplicados al Análisis de Calidad de Datos en Agricultura Digital (PDJ/CNPq UFRRJ), trabajando en el Departamento de Ciencias Ambientales de la Universidad Federal Rural de Río de Janeiro. Ha completado dos becas postdoctorales previas: la primera en Métodos de Aprendizaje Automático para la Detección de Noticias Falsas en Redes Sociales, en el Instituto de Computación de la Universidad Federal de Río de Janeiro (UFRJ), en el marco del proyecto CAPES-TecnoDigital; y la segunda en Métodos de Aprendizaje Automático Aplicados a Datos Médicos, en el Programa de Posgrado en Telemedicina y Telesalud de la Universidad Estatal de Río de Janeiro (UERJ), vinculado al proyecto CAPES-COVID. Doctor en Ingeniería de Sistemas e Informática, con énfasis en Inteligencia Artificial, por el PESC/COPPE de la Universidad Federal de Río de Janeiro (UFRJ). Maestro en Ciencias de la Computación por el Instituto de Informática de la Universidad Federal de Rio Grande do Sul (UFRGS). Posee títulos en Matemáticas y Ciencias Físicas y Matemáticas por la Universidad Nacional de Trujillo (Perú). Su trabajo científico y profesional se centra en la Inteligencia Artificial, la Ciencia de Datos, el Aprendizaje Automático y el Procesamiento del Lenguaje Natural, con aplicaciones en Educación, Salud y Agricultura Digital. También trabaja con Inteligencia Computacional (lógica difusa, redes neuronales y algoritmos genéticos), Neurociencia Computacional (seguimiento, evaluación e intervención), Sistemas Expertos, Programación Orientada a Objetos y Funcional, y desarrollo en Java, R y Python para Ciencia de Datos. Cuenta además con experiencia en estrategias de enseñanza mediadas por computadora, juegos educativos, matemáticas aplicadas y computacionales, dislexia computarizada y programación para dispositivos móviles, así como interés en la gobernanza de las tecnologías de la información.</p>\",\"flag\":\"\"},{\"name\":\"Dr. SERGIO MANUEL SERRA DA CRUZ\",\"institution\":\"UFRJ y UFRRJ\",\"country\":\"Brasil\",\"photo\":\"assets/images/congreso/sergio-serra-da-cruz.png\",\"links\":[\"http://lattes.cnpq.br/7618571401128973\",\"https://orcid.org/0000-0002-0792-8157\"],\"biography\":\"<p>Doctor en Ingeniería de Sistemas Informáticos (área de ciencia de datos) de PESC/COPPE/UFRJ (2011). Su tesis doctoral obtuvo el primer lugar en un concurso nacional organizado por la Presidencia de la República (Secretaría de Asuntos Estratégicos) en áreas estratégicas para el desarrollo nacional (Ingeniería, Sistemas e Informática) en 2011. Mestro en Ciencias de la Computación de la Universidad Federal de Río de Janeiro (2004), especializaciones en Redes de Computadoras de NCE/UFRJ (1998) y Análisis, Diseño y Gestión de Sistemas de PUC-RIO (1994). Licenciado en Química de la Universidad Federal de Río de Janeiro (1993). Actualmente es profesor en la UFRJ e investigador colaborador en la Fundación Oswaldo Cruz. Actualmente es el profesor coordinador del Programa de Posgrado en Ciencias de la Computación (PPGI/UFRJ). Profesor titular del Programa Interdisciplinario de Posgrado en Humanidades Digitales (PPGIHD/UFRRJ) y del Programa de Posgrado en Agronegocios (PPGEAGRO/UFRRJ). Revisor de varias revistas nacionales e internacionales. Coordinador del Laboratorio de Bases de Datos en la UFRRJ. Representante Institucional de la Sociedad Brasileña de Computación (SBC) en la UFRJ. Coordinador Nacional del examen POSCOMP de la SBC. Tutor del programa PÈT-SI/UFRRJ (2013-2025). Investigador asociado en AgtechGarage. Miembro del grupo Greco-DCC/UFRJ, entre otros. Líder del grupo de investigación: Gestión de Datos y Ciencia de Datos en el CNPq. Tiene experiencia en el campo de la Informática, con énfasis en Ciencia de Datos, trabajando principalmente en los siguientes temas: ingeniería de datos, bases de datos, big data, flujos de trabajo científicos, procedencia de datos, principios FAIR, datos abiertos enlazados, inteligencia artificial, ontologías, web semántica, blockchain, servicios web, computación en la nube distribuida, agricultura digital y gestión del conocimiento.</p>\",\"flag\":\"\"}]}', 1, '2026-10-09 02:02:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `congreso_contenido`
--

CREATE TABLE `congreso_contenido` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `contenido` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `congreso_contenido`
--

INSERT INTO `congreso_contenido` (`id`, `contenido`, `updated_at`) VALUES
(1, '{\"title\":\"Congreso Internacional CDIA\",\"subtitle\":\"Ciencia de Datos e Inteligencia Artificial\",\"intro\":\"Conoce a los ponentes, tarifas y medios de pago consignados para el congreso.\",\"logo\":\"assets/images/congreso/logo-cdia.jpg\",\"date\":\"Por confirmar\",\"modality\":\"Por confirmar\",\"venue\":\"Por confirmar\",\"registration_url\":\"\",\"contact_email\":\"congresocidia@fundaciondu.org\",\"sponsors\":[\"Bitel\",\"Simmetryc\"],\"fees\":[{\"audience\":\"Estudiantes UNTELS\",\"amount\":\"S/ 50.00\"},{\"audience\":\"Personal UNTELS\",\"amount\":\"S/ 100.00\"},{\"audience\":\"Estudiantes en general\",\"amount\":\"S/ 100.00\"},{\"audience\":\"Público en general\",\"amount\":\"S/ 200.00\"}],\"certifications\":[\"La tarifa incluye certificación de Perú.\",\"Certificación doble (Perú–China): S/ 50.00 adicionales.\",\"Certificación triple (Perú–China–Brasil): S/ 100.00 adicionales.\"],\"payment\":{\"bank\":\"Banco de Crédito del Perú (BCP)\",\"holder\":\"Fundación para el Desarrollo Universitario UNTELS\",\"account\":\"194-6929357-0-03\",\"cci\":\"00219400692935700397\",\"yape\":\"940 404 384\",\"yape_holder\":\"Luzbeth Karin Navarrete Leal\",\"note\":\"Confirma que estos datos sigan vigentes con la organización antes de realizar un pago.\"},\"speakers\":[{\"name\":\"Dr. SERGIO MANUEL SERRA DA CRUZ\",\"country\":\"Brasil\",\"flag\":\"????????\",\"institution\":\"UFRJ y UFRRJ\",\"photo\":\"sergio-serra-da-cruz.png\",\"biography\":\"Doctor en Ingeniería de Sistemas Informáticos (área de ciencia de datos) de PESC/COPPE/UFRJ (2011). Su tesis doctoral obtuvo el primer lugar en un concurso nacional organizado por la Presidencia de la República (Secretaría de Asuntos Estratégicos) en áreas estratégicas para el desarrollo nacional (Ingeniería, Sistemas e Informática) en 2011. Mestro en Ciencias de la Computación de la Universidad Federal de Río de Janeiro (2004), especializaciones en Redes de Computadoras de NCE/UFRJ (1998) y Análisis, Diseño y Gestión de Sistemas de PUC-RIO (1994). Licenciado en Química de la Universidad Federal de Río de Janeiro (1993). Actualmente es profesor en la UFRJ e investigador colaborador en la Fundación Oswaldo Cruz. Actualmente es el profesor coordinador del Programa de Posgrado en Ciencias de la Computación (PPGI/UFRJ). Profesor titular del Programa Interdisciplinario de Posgrado en Humanidades Digitales (PPGIHD/UFRRJ) y del Programa de Posgrado en Agronegocios (PPGEAGRO/UFRRJ). Revisor de varias revistas nacionales e internacionales. Coordinador del Laboratorio de Bases de Datos en la UFRRJ. Representante Institucional de la Sociedad Brasileña de Computación (SBC) en la UFRJ. Coordinador Nacional del examen POSCOMP de la SBC. Tutor del programa PÈT-SI/UFRRJ (2013-2025). Investigador asociado en AgtechGarage. Miembro del grupo Greco-DCC/UFRJ, entre otros. Líder del grupo de investigación: Gestión de Datos y Ciencia de Datos en el CNPq. Tiene experiencia en el campo de la Informática, con énfasis en Ciencia de Datos, trabajando principalmente en los siguientes temas: ingeniería de datos, bases de datos, big data, flujos de trabajo científicos, procedencia de datos, principios FAIR, datos abiertos enlazados, inteligencia artificial, ontologías, web semántica, blockchain, servicios web, computación en la nube distribuida, agricultura digital y gestión del conocimiento.\",\"links\":[\"http://lattes.cnpq.br/7618571401128973\",\"https://orcid.org/0000-0002-0792-8157\"]},{\"name\":\"Dr. JORGE JUAN ZAVALETA GAVIDIA\",\"country\":\"Brasil\",\"flag\":\"????????\",\"institution\":\"UFRRJ\",\"photo\":\"jorge-zavaleta-gavidia.jpg\",\"biography\":\"Investigador con postdoctorado en Métodos de Aprendizaje Automático Aplicados al Análisis de Calidad de Datos en Agricultura Digital (PDJ/CNPq UFRRJ), trabajando en el Departamento de Ciencias Ambientales de la Universidad Federal Rural de Río de Janeiro. Ha completado dos becas postdoctorales previas: la primera en Métodos de Aprendizaje Automático para la Detección de Noticias Falsas en Redes Sociales, en el Instituto de Computación de la Universidad Federal de Río de Janeiro (UFRJ), en el marco del proyecto CAPES-TecnoDigital; y la segunda en Métodos de Aprendizaje Automático Aplicados a Datos Médicos, en el Programa de Posgrado en Telemedicina y Telesalud de la Universidad Estatal de Río de Janeiro (UERJ), vinculado al proyecto CAPES-COVID. Doctor en Ingeniería de Sistemas e Informática, con énfasis en Inteligencia Artificial, por el PESC/COPPE de la Universidad Federal de Río de Janeiro (UFRJ). Maestro en Ciencias de la Computación por el Instituto de Informática de la Universidad Federal de Rio Grande do Sul (UFRGS). Posee títulos en Matemáticas y Ciencias Físicas y Matemáticas por la Universidad Nacional de Trujillo (Perú). Su trabajo científico y profesional se centra en la Inteligencia Artificial, la Ciencia de Datos, el Aprendizaje Automático y el Procesamiento del Lenguaje Natural, con aplicaciones en Educación, Salud y Agricultura Digital. También trabaja con Inteligencia Computacional (lógica difusa, redes neuronales y algoritmos genéticos), Neurociencia Computacional (seguimiento, evaluación e intervención), Sistemas Expertos, Programación Orientada a Objetos y Funcional, y desarrollo en Java, R y Python para Ciencia de Datos. Cuenta además con experiencia en estrategias de enseñanza mediadas por computadora, juegos educativos, matemáticas aplicadas y computacionales, dislexia computarizada y programación para dispositivos móviles, así como interés en la gobernanza de las tecnologías de la información.\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=110178\",\"http://lattes.cnpq.br/5989368756609995\"]},{\"name\":\"Dr. RENATO CERCEAU\",\"country\":\"Brasil\",\"flag\":\"????????\",\"institution\":\"UFRRJ\",\"photo\":\"renato-cerceau.jpg\",\"biography\":\"Es licenciado en Medicina (UFF, 1997), licenciado en Derecho (UFF, 2022), máster en Ingeniería Biomédica (COPPE/UFRJ, 2004) y doctor en Ingeniería de Sistemas e Informática (COPPE/UFRJ, 2018). Actualmente, investigador en el Instituto de Investigación Alda y en la Universidad Federal Rural de Río de Janeiro (UFRRJ), socio de la empresa de tecnología sanitaria TervisSaúde y especialista en regulación de la salud complementaria en la Agencia Nacional de Salud Complementaria (ANS). Cuento con experiencia en docencia, investigación, innovación y extensión en las áreas de Medicina y Derecho, centrándome principalmente en los siguientes temas: salud colectiva/salud pública, planificación sanitaria, salud complementaria (seguro médico) y Ciencia de Datos/Inteligencia Artificial.\",\"links\":[\"http://lattes.cnpq.br/1652132080233697\",\"https://orcid.org/0000-0003-3953-4715\"]},{\"name\":\"MARIANA GONÇALVES DA COSTA\",\"country\":\"Brasil\",\"flag\":\"????????\",\"institution\":\"UFRJ\",\"photo\":\"mariana-goncalves-da-costa.jpg\",\"biography\":\"Licenciada en Literatura Portuguesa e Inglesa por la Universidad Federal de Río de Janeiro (UFRJ), actualmente cursa una Maestría en Metodología y Técnicas de Computación en PPGI/UFRJ, donde desarrolla investigación en la intersección de la lingüística y la informática, centrándose en el análisis e identificación de expresiones multipalabra. Colabora con el portal inCorpora, en el área de Procesamiento del Lenguaje Natural, y con el proyecto de extensión ReHDLinguistics. Entre 2018 y 2022, participó en el proyecto PREDICAR, coordinado por la Prof. Dra. Marcia Machado Vieira (UFRJ), basado en la Gramática de Construcciones Basada en el Uso. Actualmente, forma parte del sector de investigación del proyecto de extensión Minerv@sDigitais, cuyo objetivo es fomentar la participación femenina en las áreas STEM. Cuenta con experiencia en traducción, edición técnica y enseñanza de idiomas. Sus principales intereses son el Procesamiento del Lenguaje Natural, las Humanidades Digitales, la Ciencia de Datos, la Edición y la Traducción.\",\"links\":[\"http://lattes.cnpq.br/1485694235655537\",\"https://orcid.org/0000-0002-8088-0794\"]},{\"name\":\"RONILSON RODRIGUES PINHO\",\"country\":\"Brasil\",\"flag\":\"????????\",\"institution\":\"CEFET/RJ y UFRJ\",\"photo\":\"ronilson-rodrigues-pinho.jpg\",\"biography\":\"Posee un título en Tecnología de Procesamiento de Datos por la Facultad Integrada Simonsen (1995), un posgrado en Análisis y Diseño de Sistemas por la PUC-RJ y una maestría en Modelado Matemático y Computacional por la UFRRJ. Actualmente es candidato a doctorado (desde 2019) en Ciencias de la Computación en la UFRJ. Es profesor titular de EBTT en el CEFET/RJ, Campus María da Graça, donde imparte clases en el curso de Técnico en Automatización Industrial y en el programa de pregrado de Sistemas de Información.Profesor na Cefet-RJ.\",\"links\":[\"https://www.linkedin.com/in/ronilson-pinho-1750a336/\",\"https://orcid.org/0009-0008-0852-2803\"]},{\"name\":\"ALESSANDRA CARBONEL\",\"country\":\"Brasil\",\"flag\":\"????????\",\"institution\":\"UFRJ\",\"photo\":\"alessandra-carbonel.jpg\",\"biography\":\"Licenciado en Meteorología por la Universidad Federal de Río de Janeiro (UFRJ) y cuento con experiencia en investigación científica en diversas áreas de meteorología, climatología y astrofísica. Experiencia en el procesamiento y la verificación de la consistencia de grandes bases de datos ambientales mediante diversos lenguajes de programación científica, posee conocimientos computacionales que me permiten trabajar con diferentes tipos de datos, además de crear mapas que facilitan el análisis y la predicción meteorológica y climática para diversas aplicaciones.Experiencia científica en las áreas de meteorología, climatología y astrofísica, con aplicaciones de técnicas estadísticas a datos ambientales y análisis de datos, principalmente datos meteorológicos y de calidad del aire obtenidos a través de redes de monitoreo con sensores in situ y remotos disponibles, como datos atmosféricos obtenidos de radiosondas e instrumentos como SODAR y LIDAR. En el campo de la astrofísica, experiencia en el procesamiento y la manipulación de datos obtenidos a través del telescopio espacial Chandra, los telescopios ópticos ESO/MPI, ESO/VLT y Cassini, y el interferómetro del radiotelescopio VLA.\",\"links\":[\"http://lattes.cnpq.br/9417222739212083\"]},{\"name\":\"FELIPE RODRIGUES CALÉ\",\"country\":\"Brasil\",\"flag\":\"????????\",\"institution\":\"UFRJ\",\"photo\":\"felipe-rodrigues-cale.jpg\",\"biography\":\"Profesional con más de 10 años de experiencia en desarrollo de software, con una sólida trayectoria en entornos ágiles, proyectos a gran escala y tecnologías emergentes. Desde 2020, he liderado equipos técnicos en soluciones web y móviles, centrándome en la escalabilidad, la automatización de pruebas y la innovación, incluyendo iniciativas con blockchain, IoT y computación en la nube. Gran capacidad para garantizar entregables de alto valor añadido y calidad constante.Ingeniero de Software y Líder Técnico | Java, Spring Boot, Angular, Cloud y Web3 | Estudiante de Maestría en Informática en la UFRJ.\",\"links\":[\"http://lattes.cnpq.br/5368631771058240\"]},{\"name\":\"MARY MANHÃES\",\"country\":\"Brasil\",\"flag\":\"????????\",\"institution\":\"UFF\",\"photo\":\"mary-manhaes.jpg\",\"biography\":\"Profesora de Informática en la Universidad Federal Fluminense (UFF) desde 2017. Profesora coordinadora de cursos en el consorcio CEDERJ/UENF desde 2017. Doctora en Ingeniería de Sistemas yComputación PESC/COPPE/UFRJ (2015), título de la tesis \\\"Predicción del rendimiento académico de estudiantes de pregrado mediante minería de datos educativos\\\". Maestría en Informática por la VrijeUniversiteitBrussel (1999) Programa Capes/Cofecub EMOOSE (Máster Europeo en Ciencias de la Ingeniería de Software Orientada a Objetos). Licenciada en Matemáticas por la Facultad de Filosofía de Campos (1997). Experiencia profesional como profesora universitaria desde 1993. Experiencia en educación a distancia en el consorcio CEDERJ desde 2000. Especialización en Informática, centrándose en los siguientes temas: Ciencia de Datos, Minería de Datos, Inteligencia Artificial, Estadística, Gestión del Conocimiento, Ingeniería de Software, Educación a Distancia, Informática en la Educación, Software Educativo y Bases de Datos.\",\"links\":[\"http://lattes.cnpq.br/6973300839713560\",\"https://orcid.org/0000-0002-0605-3117\"]},{\"name\":\"RODRIGO RAMOS GUIMARÃES\",\"country\":\"Brasil\",\"flag\":\"????????\",\"institution\":\"UFRJ\",\"photo\":\"rodrigo-ramos-guimaraes.jpg\",\"biography\":\"Graduado en Análisis y Desarrollo de Sistemas por la Universidad Estácio de Sá (2010) y realizó estudios de posgrado en Gobernanza de TI y Desarrollo Móvil (MIT). Posee una sólida experiencia en el campo de la Informática, con especialización en Arquitectura de Sistemas Informáticos, trabajando en proyectos de desarrollo de software, integración de sistemas, infraestructura y soluciones corporativas. Actualmente cursa una maestría en el Programa de Posgrado en Informática (PPGI) de la Universidad Federal de Río de Janeiro, donde desarrolla una investigación centrada en tecnología, arquitectura de sistemas e innovación aplicada.\",\"links\":[\"http://lattes.cnpq.br/7842475190791460\",\"https://orcid.org/0009-0009-5515-9485\"]},{\"name\":\"Dr. FLABIO ALFONSO GUTIERREZ SEGURA\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"Universidad Nacional de Piura\",\"photo\":\"flabio-gutierrez-segura.jpg\",\"biography\":\"Doctor en Matemática en la Univ. Nacional de Trujillo (Perú), Estudios Doctorado en Informática - Univ. Politécnica de Valencia (España). Magister en Matemática Aplicada - Universidad Nacional de Piura (Perú), Magister en Ciencias de la computación por la Universidad de Cantabria (España). Licenciado en Matemática UNT-Trujillo. Docente del Dpto. de Matemática - Universidad Nacional de Piura. Investigador en Inteligencia Artificial (Sistemas inteligentes, Lógica difusa, Optimización Difusa, Machine Learning, Redes Neuronales, Algoritmos Genéticos).\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=21770\",\"https://www.researchgate.net/profile/Flabio-Gutierrez-Segura\"]},{\"name\":\"Dr. MANUEL ABELARDO ALCÁNTARA RAMÍREZ\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"UNTELS\",\"photo\":\"manuel-alcantara-ramirez.jpg\",\"biography\":\"Doctor en Ingeniería de Sistemas, Doctor en Matemáticas, Magister en Ingeniería de Sistemas, Ingeniero de Sistemas y Licenciado enMatemáticas, Investigador Renacyt, Responsable y titular de “CIDIA - Grupo de Investigación en Ciencia de Datos e Inteligencia Artificial” de la UNTELS, Miembro del grupo Internacional de Investigación CDIA. Director de la Escuela Profesional de Ingeniería en Ciencia de Datos e Inteligencia artificial de la Universidad Nacional Tecnológica de Lima Sur (UNTELS). Docente en el pre y Posgrado de la Universidad Nacional del Callao (UNAC). Docente Principal en la UNTELS.\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=105356\"]},{\"name\":\"Dr. JOSE ALFREDOHERRERA QUISPE\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"UNMSM\",\"photo\":\"jose-herrera-quispe.jpg\",\"biography\":\"Doctor en Ciencia de la Computación especializado en Inteligencia Artificial, Minería de Datos y Computación Ambiental. Obtuvo su doctorado en la UNSA con Beca CONCYTEC y pasantía de formación en el LMTG  Universidad Paul Sabatier (Francia). Concluyo el Mastering in Innovation & DesignThinking en el MIT como parte del programa de profesionalización. Fue Director de Información y Gestión del Conocimiento en INAIGEM (MINAM), donde impulsó laboratorios de visión computacional, IA para detección de avalanchas, IoT en subcuencas de riesgo y análisis social. Lideró el modelo de gestión del conocimiento institucional, el Geoportal de mapas de riesgo y un inventario 3D de cordilleras, con proyectos de robótica para glaciares. En la UNSA, fue Director de Investigación, creó la marca UNSA-Investiga con CONCYTEC, dirigió la maestría en Informática financiada por Cienciactiva y fortaleció la producción científica mediante fondos canon. Profesor en la primera escuela acreditada por ICACIT en Computación. Actualmente es Profesor Principal y Director de la Escuela de Ciencia de la Computación en la UNMSM, liderando iniciativas académicas, científicas y de responsabilidad social.\",\"links\":[\"https://dina.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=19\"]},{\"name\":\"Dr. NILTON ARCE FERNANDEZ\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"Universidad Nacional de Jaén\",\"photo\":\"nilton-arce-fernandez.jpg\",\"biography\":\"Licenciado en Matemáticas, Magister en Administración con mención en Gerencia Empresarial, Magister en Ciencias con mención en Matemática Aplicada y Doctor en Ciencias Matemáticas por la Universidad Nacional de Piura. Investigador en temas de Modelado Matemático, Inteligencia Artificial e Integración de las TIC en la Enseñanza de las Ciencias Básicas. Docente nombrado a tiempo completo en la Universidad Nacional de Jaén. Actualmente calificado en el Nivel III en RENACYT.\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=93474\"]},{\"name\":\"Dr. MAXIMILIANO EPIFANIOASIS LOPEZ\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"UNASAM\",\"photo\":\"maximiliano-lopez.jpg\",\"biography\":\"Doctor en Ciencia e Ingeniería de la Computación y se desempeña como docente de Matemáticas y Computación en la Facultad de Ciencias, Departamento Académico de Matemática, de la Universidad Nacional Santiago Antúnez de Mayolo (UNASAM), en la ciudad de Huaraz. Bachiller en Matemática y la Licenciatura en Matemática, con mención en Investigación de Operaciones e Informática. Además Bachiller y el título profesional en Ingeniería Informática y de Sistemas. Cuenta con una Maestría en Computación e Informática por la UNASAM y una Maestría en Matemática. Ha complementado su formación académica con una Diplomatura en Inteligencia Artificial en la Pontificia Universidad Católica de Chile. Actualmente, es coordinador del Grupo de Investigación en Matemática Difusa y Aprendizaje de Máquina (MATDIAMA). Sus líneas de investigación de interés incluyen la Inteligencia Artificial, la Lógica Difusa, el Aprendizaje de Máquina, la Computación Gráfica, la Optimización y los Métodos Numéricos.\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=29077\"]},{\"name\":\"Dr. RAUL ALFREDO SANCHEZ ANCAJIMA\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"Universidad Nacional de Tumbes\",\"photo\":\"raul-sanchez-ancajima.jpg\",\"biography\":\"Doctor en Matemática por la Escuela de Posgrado de la Universidad Nacional de Trujillo. Docente investigador de la Universidad Nacional de Tumbes (Docente Principal). Mi interés para investigación es en el área de inteligencia artificial, minería de datos, educación regresión logística, cálculo fraccionario. Soy Licenciado en Matemática y Maestro en Ciencias con mención en Matemática Aplicada por la Universidad Nacional de Piura, Responsable, ordenado y con condiciones para trabajar en equipo.\",\"links\":[\"https://ctivitae.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=18514\"]},{\"name\":\"Dr. EDMUNDO RUBEN VERGARA MORENO\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"No especificada en el documento\",\"photo\":\"edmundo-vergara-moreno.jpg\",\"biography\":\"Doctor en Ciencias Matemáticas (Granada-España, 1999), con estudios de Pregrado y maestría en la Universidad Nacional de Trujillo. Profesor visitante en la Universidad de Loja Ecuador (1994). Investigador visitante en las universidades de: Las Palmas de Gran Canaria (1996), Universidad de Granada (2002, 2012), Universidad Estadual Pulista (2002, 2005, 2013) y Universidad Saarlandes (2007). se dedica al área de la optimización Lineal clásica, difusa y los métodos heurísticos y sus aplicaciones.\",\"links\":[\"https://dina.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do?id_investigador=1239\"]},{\"name\":\"JEYNER SUAREZ GUERRERO\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"Universidad Nacional de Piura\",\"photo\":\"jeyner-suarez-guerrero.jpg\",\"biography\":\"Licenciado en Matemática por la Universidad Nacional de Piura, con experiencia en ciencia de datos, modelado estadístico y aprendizaje automático. Domina redes bayesianas, series de tiempo, DEA, árboles de decisión, SVM y redes neuronales. Se ha desempeñado como Ingeniero de Datos en el Vicerrectorado de Investigación de la UNP y como docente en análisis de datos en CAMU–Costa Rica (big data, machine learning y deeplearning con Python). Ha sido ponente en el II y III Congreso Internacional de Machine Learning, Lógica Difusa y sus Aplicaciones Prácticas y en la XIII Conferencia Académica del Programa de Intercambio Educativo de la Universidad del Pacífico. Maneja Python, R, SQL, C++ y MATLAB.\",\"links\":[]},{\"name\":\"LENIN HERRERA PADILLA\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"Universidad Nacional de Ingeniería\",\"photo\":\"lenin-herrera-padilla.jpg\",\"biography\":\"Lic. en Matemática por la Universidad Nacional de Piura y estudiante de la Maestría en Inteligencia Artificial en la Universidad Nacional de Ingeniería, con sólida base en modelado matemático, ecuaciones diferenciales y optimización aplicada al desarrollo de soluciones de inteligencia artificial. Mi línea de investigación se centra en las Redes Neuronales Informadas por la Física (PINNs) para la resolución de problemas directos e inversos, integrando el conocimiento físico con modelos de deeplearning. Cuenta con experiencia en el desarrollo de proyectos de machine learning para regresión, clasificación y agentes inteligentes, utilizando Python, TensorFlow, DeepXDE, scikit-learn, pandas y entornos como Google Colab y AWS.\",\"links\":[]},{\"name\":\"YOSBI GOLLES PAICO\",\"country\":\"Perú\",\"flag\":\"????????\",\"institution\":\"Universidad Peruana Cayetano Heredia\",\"photo\":\"yosbi-golles-paico.jpg\",\"biography\":\"Matemático egresado de la UNP. Ha finalizado la maestría en Matemática Aplicada de la UNP. Con formación en modelamiento matemático, optimización, machine learning y cómputo científico. Actualmente es investigador en la Universidad Peruana Cayetano Heredia, donde desarrolla investigación en reconstrucción holográfica sin lentes para microscopía, mediante propagación numérica de ondas y recuperación iterativa de fase. Previamente fue investigador en la Universidad Nacional de Trujillo, trabajando en investigación de operaciones, programación entera mixta y modelos de localización y ruteo de vehículos aplicados a problemas reales de planificación logística. Su trabajo integra modelos matemáticos y físicos con herramientas computacionales. Ha obtenido el primer lugar en el VII Concurso de Iniciación Científica “Matemática y sus Aplicaciones” (X CIMAC, 2021).\",\"links\":[]},{\"name\":\"AGUILAR GUTIERREZ LUIS ANTONIO\",\"country\":\"Chile\",\"flag\":\"????????\",\"institution\":\"No especificada en el documento\",\"photo\":\"\",\"biography\":\"Especialista en el área de Inteligencia Artificial, concretamente en Machine Learning y Deep Learning. En estas áreas he desarrollado diversas aplicaciones usando aprendizaje supervisado como no supervisado. Actualmente miembro del grupo de investigacionKapAITech: kapaitech.com/\",\"links\":[\"https://dina.concytec.gob.pe/appDirectorioCTI/VerDatosInvestigador.do;jsessionid=69464bf279c103a1a938c9384659?id_investigador=67956\"]}]}', '2026-10-06 04:04:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `convenios`
--

CREATE TABLE `convenios` (
  `id` int(11) NOT NULL,
  `institucion` varchar(180) NOT NULL,
  `pais` varchar(100) NOT NULL,
  `ciudad` varchar(120) DEFAULT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `ubicacion_google` varchar(500) DEFAULT NULL,
  `tipo` varchar(120) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `fecha_evento` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `hora_evento` time DEFAULT NULL,
  `lugar` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `link_inscripcion` varchar(255) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `estado` enum('proximo','finalizado','cancelado') NOT NULL DEFAULT 'proximo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `titulo`, `fecha_evento`, `fecha_fin`, `hora_evento`, `lugar`, `imagen`, `descripcion`, `link_inscripcion`, `categoria`, `estado`, `created_at`, `updated_at`) VALUES
(4, 'I Congreso Internacional CIDIA', '2026-11-24', '2026-11-26', '08:00:00', 'Virtual', NULL, '<p>Es un espacio internacional de encuentro académico y profesional que reunirá a expertos, investigadores y estudiantes para compartir conocimientos, experiencias y avances en ciencia de datos e inteligencia artificial, impulsando la innovación y la colaboración global.</p>', 'https://docs.google.com/forms/d/1ou2VGKG6iCtt8c9c5iTrHmhAZb-1dJqsitjoOSIzhfQ/viewform?ts=6ac52dce&edit_requested=true&fbzx=-9218151107962792597', 'CONGRESO', 'proximo', '2026-10-09 02:07:29', '2026-10-09 02:07:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `correo` varchar(180) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `asunto` varchar(120) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `estado` enum('pendiente','respondido') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `miembros`
--

CREATE TABLE `miembros` (
  `id` int(11) NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `cargo` varchar(120) NOT NULL,
  `correo` varchar(180) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 99,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `miembros`
--

INSERT INTO `miembros` (`id`, `nombre`, `cargo`, `correo`, `orden`, `imagen`, `created_at`, `updated_at`) VALUES
(1, 'José Yudberto Vilca Ccolque', 'Vicepresidente', 'jvilca@fundacion.org', 2, NULL, '2026-09-25 15:36:31', '2026-09-30 16:48:09'),
(2, 'Melissa Fatima Muñante Toledo', 'Secretaria', NULL, 3, NULL, '2026-09-25 15:36:31', '2026-09-25 15:36:31'),
(3, 'Manuel Abelardo Alcántara Ramírez', 'Tesorero', NULL, 4, NULL, '2026-09-25 15:36:31', '2026-09-25 15:36:31'),
(4, 'Edwin Augusto Vigo Sánchez', 'Vocal', NULL, 5, NULL, '2026-09-25 15:36:31', '2026-09-25 15:36:31'),
(5, 'José Carlos Goicochea Ponce', 'Gerente general', NULL, 6, '/fundaciondu/public/uploads/member_6abd37f1d236f2.23737454.jpg', '2026-09-25 15:36:31', '2026-09-30 16:25:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

CREATE TABLE `noticias` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `autor` varchar(180) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_evento` date DEFAULT NULL,
  `descripcion_corta` varchar(255) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `es_destacada` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `popup_config`
--

CREATE TABLE `popup_config` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `image_path` varchar(500) NOT NULL DEFAULT '',
  `link_url` varchar(1000) NOT NULL DEFAULT '',
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `display_seconds` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `popup_config`
--

INSERT INTO `popup_config` (`id`, `image_path`, `link_url`, `starts_on`, `ends_on`, `display_seconds`, `active`, `updated_at`) VALUES
(1, '/fundaciondu/public/uploads/3886f19db2a327ff0cab054d2187fa3f.jpeg', 'http://localhost/fundaciondu/public/congreso?id=1', '2026-10-07', '2026-10-14', 0, 1, '2026-10-07 17:13:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programas`
--

CREATE TABLE `programas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `autor` varchar(150) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `lugar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `duracion` varchar(120) DEFAULT NULL,
  `modalidad` varchar(20) DEFAULT NULL,
  `horario` varchar(180) DEFAULT NULL,
  `frecuencia` varchar(120) DEFAULT NULL,
  `dirigido_a` text DEFAULT NULL,
  `objetivos` text DEFAULT NULL,
  `temario` longtext DEFAULT NULL,
  `requisitos` text DEFAULT NULL,
  `certificacion` text DEFAULT NULL,
  `docentes` longtext DEFAULT NULL,
  `inversion` text DEFAULT NULL,
  `descuentos` text DEFAULT NULL,
  `vacantes` int(11) DEFAULT NULL,
  `contacto_telefono` varchar(60) DEFAULT NULL,
  `contacto_whatsapp` varchar(60) DEFAULT NULL,
  `contacto_correo` varchar(254) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `programas`
--

INSERT INTO `programas` (`id`, `titulo`, `categoria`, `imagen`, `autor`, `descripcion`, `link`, `fecha_inicio`, `fecha_fin`, `lugar`, `created_at`, `updated_at`, `duracion`, `modalidad`, `horario`, `frecuencia`, `dirigido_a`, `objetivos`, `temario`, `requisitos`, `certificacion`, `docentes`, `inversion`, `descuentos`, `vacantes`, `contacto_telefono`, `contacto_whatsapp`, `contacto_correo`) VALUES
(6, 'Gestion Hospitalaria Avanzada', 'SALUD', '/fundaciondu/public/uploads/img_6ab9a19fcf60d9.67550403.jpg', 'Fundación Carlos Slim', '<p>Especialización en optimización de recursos y servicios de salud para el sector público y privado</p>', 'https://www.untels.edu.pe/', '2026-09-28', '2026-10-01', NULL, '2026-09-27 23:07:11', '2026-10-02 15:55:51', '4 días', 'presencial', '08:30', 'Diaria', 'Estudiantes', 'Aprende Salud', '[{\"titulo\":\"Salud\",\"descripcion\":\"Salud\"}]', 'Estudiantes universitarios', 'SI', '[{\"foto\":\"/fundaciondu/public/uploads/img_6aba9fd34fbbf5.91496052.jpg\",\"nombre\":\"Enrique\",\"cargo\":\"Docente\",\"descripcion\":\"Docente\",\"correo\":\"enrique@gmail.com\",\"linkedin\":null}]', 'S/. 300', 'Estudiantes univesitarios', 50, '987654321', '987654321', 'informes@fundacion.org');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reclamaciones`
--

CREATE TABLE `reclamaciones` (
  `id` int(11) NOT NULL,
  `folio` varchar(24) NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `tipo_documento` varchar(20) NOT NULL,
  `numero_documento` varchar(20) DEFAULT NULL,
  `domicilio` varchar(250) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(180) NOT NULL,
  `tipo_bien` enum('producto','servicio') NOT NULL,
  `fecha_compra` date DEFAULT NULL,
  `bien_contratado` varchar(250) NOT NULL,
  `monto_reclamado` decimal(12,2) DEFAULT NULL,
  `tipo` enum('reclamo','queja') NOT NULL,
  `detalle` text NOT NULL,
  `pedido` text NOT NULL,
  `estado` enum('pendiente','respondida') NOT NULL DEFAULT 'pendiente',
  `respuesta` text DEFAULT NULL,
  `acepta_datos` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$ecAU10/.qBdr9jKeY2Y3TecLeNb8H/sn.p1PEQQk.VOi0PNvSmV0G', 'admin', '2026-09-25 15:37:59');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `congresos`
--
ALTER TABLE `congresos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `congreso_contenido`
--
ALTER TABLE `congreso_contenido`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `convenios`
--
ALTER TABLE `convenios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `miembros`
--
ALTER TABLE `miembros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `popup_config`
--
ALTER TABLE `popup_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `programas`
--
ALTER TABLE `programas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `reclamaciones`
--
ALTER TABLE `reclamaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `folio` (`folio`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `congresos`
--
ALTER TABLE `congresos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `convenios`
--
ALTER TABLE `convenios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `miembros`
--
ALTER TABLE `miembros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `programas`
--
ALTER TABLE `programas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `reclamaciones`
--
ALTER TABLE `reclamaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
