<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProveedoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Genera 10,000 proveedores con nombres e identidades 100% realistas de la industria del plástico
     */
    public function run(): void
    {
        $this->command->info('>>> [2/7] Generando 10,000 Proveedores realistas de la industria del plástico...');

        $total = 10000;
        $batchSize = 1000;

        // Prefijos y rubros corporativos reales del plástico, embalaje y materias primas
        $tiposEmpresa = [
            'Industrias Plásticas', 'Distribuidora de Polímeros', 'Manufacturas y Resinas',
            'Corporación Plástica', 'Empaques y Envases', 'Polietilenos y Derivados',
            'Termoencogibles y Películas', 'Fábrica de Bolsas y Empaques', 'Inversiones y Plásticos',
            'Suministros Industriales de Plástico', 'Importadora y Distribuidora de Resinas',
            'Comercializadora de Desechables', 'Plásticos Industriales', 'Envases Termoformados',
            'Empaques Biodegradables', 'Soluciones Plásticas Integrales', 'Logística y Distribución de Polímeros',
            'Procesadora de Plásticos y Moldes', 'Maquilas y Termoplásticos', 'Consorcio Plástico',
            'Grupo Empresarial de Empaques', 'Recicladora y Fabricante de Polímeros', 'Química y Plásticos',
            'Mundo Plástico y Desechables', 'Fábrica de Contenedores y Cubetas', 'Distribuidora de Películas Estirables',
            'Manufacturas Termoplásticas', 'Inversiones en Empaques Flexibles', 'Comercializadora de Resinas y Pigmentos',
            'Compañía Salvadoreña de Plásticos', 'Envases Plásticos del Pacífico', 'Corporación de Desechables',
            'Polímeros Centroamericanos', 'Transformadora de Plásticos', 'Termoformados y Empaques',
            'Distribuidora Mayorista de Polietileno', 'Fábrica de Desechables Térmicos', 'Tecnoempaques',
            'Polímeros Sintéticos', 'Empaques Ecológicos y Plásticos'
        ];

        // Denominaciones territoriales y comerciales salvadoreñas
        $identidadesTerritoriales = [
            'de Oriente', 'Cuscatlán', 'San Miguel', 'de El Salvador', 'Migueleño', 'Centroamericano',
            'del Golfo', 'Chaparrastique', 'La Sultana', 'del Pacífico', 'Morazán', 'Usulután',
            'Roosevelt', 'San Salvador', 'La Paz', 'Santa Ana', 'del Tránsito', 'Continental',
            'Panamericano', 'Occidental', 'del Sol', 'Intercontinental', 'Metropolitano', 'El Volcán',
            'Conchagua', 'La Unión', 'Tecana', 'San Antonio', 'San José', 'Santa María', 'El Milagro',
            'La Bendición', 'Guadalupano', 'San Rafael', 'San Francisco', 'El Tamarindo', 'Los Héroes',
            'Merliot', 'Soyapango', 'San Marcos', 'Apastepeque', 'Coatepeque', 'Ilopango', 'El Boquerón',
            'Cacahuatique', 'Jiboa', 'Goascorán', 'Torola', 'Paz', 'Atlacatl', 'Lempa', 'Chinameca',
            'Moncagua', 'Gotera', 'Berlín', 'Jiquilisco', 'Santiago de María', 'Sesori', 'San Alejo',
            'Intipucá', 'Izalco', 'Apopa', 'Nejapa', 'Zacatecoluca', 'Sensuntepeque', 'Ilobasco'
        ];

        $especialidades = [
            'y Empaques', 'y Resinas', 'y Derivados', 'y Desechables', 'y Polietileno',
            'y Polímeros', 'y Contenedores', 'y Termoplásticos', 'Avanzados', 'Especializados',
            'del Norte', 'del Sur', 'Industrial', 'Modernos', 'Ecológicos',
            'de Calidad', 'y Logística', 'y Suministros', 'Internacional', 'Regional',
            'del Valle', 'Premium', 'de Alta Densidad', 'Flexible', 'Biodegradable'
        ];

        $sociedades = [
            'S.A. de C.V.',
            'S.A. de C.V.',
            'S.A. de C.V.',
            'Ltda. de C.V.',
            'S.A.',
            'de C.V.'
        ];

        // Zonas industriales y comerciales reales
        $direccionesBase = [
            'Zona Industrial Plan de La Laguna, Calle El Progreso #42, Antiguo Cuscatlán',
            'Parque Industrial San Bartolo, Calle Circunvalación Norte Nave #15, Ilopango',
            'Carretera Panamericana Km 138, Desvío a Febles, San Miguel',
            'Boulevard del Ejército Nacional Km 5 1/2, Soyapango, San Salvador',
            'Avenida Roosevelt Sur, Edificio Plaza Florencia Local 4-B, San Miguel',
            'Zona Industrial Merliot, Calle L-3 Polígono D #24, Santa Tecla',
            'Carretera a Santa Ana Km 29, Complejo Exportec Nave #8, Ciudad Arce',
            'Prolongación 4ª Calle Poniente #512, Barrio San Felipe, San Miguel',
            'Carretera del Litoral Km 112, Desvío San Dionisio, Usulután',
            'Zona Franca San Marcos, Edificio 3 Módulo A, San Marcos, San Salvador',
            'Carretera Ruta Militar Km 142, Barrio El Calvario, Santa Rosa de Lima',
            'Boulevard Los Próceres, Pasaje 2 Casa #18, San Salvador',
            'Avenida Las Amapolas #35, Colonia San Francisco, San Salvador',
            'Carretera Panamericana Km 140, Frente a Gasolinera Uno, San Miguel',
            'Barrio La Merced, 2ª Avenida Norte #305, Usulután',
            'Carretera a La Unión Km 155, Cantón El Carmen, La Unión',
            'Parque Logístico Nejapa, Módulo B-4, Carretera a Quezaltepeque',
            'Colonia Ciudad Jardín, 6ª Calle Oriente y 8ª Avenida Sur #110, San Miguel',
            'Barrio El Centro, 3ª Calle Poniente #204, San Francisco Gotera, Morazán',
            'Carretera a Moncagua Km 135, Desvío El Platanar, San Miguel'
        ];

        // Contactos ejecutivos masculinos y femeninos coherentes
        $contactosMasculinos = [
            'Roberto Carlos', 'José Antonio', 'Fernando José', 'Carlos Manuel',
            'Francisco Javier', 'Mario Ernesto', 'David Alejandro', 'Walter Antonio',
            'Douglas René', 'Nelson Mauricio', 'Jorge Alberto', 'Ricardo Alfredo',
            'Guillermo Enrique', 'Hugo Ernesto', 'Edgar Alexander', 'Héctor Rolando'
        ];
        $titulosMasculinos = ['Ing.', 'Lic.', 'Sr.', 'Mtro.', 'Dr.'];

        $contactosFemeninos = [
            'María Elena', 'Patricia Carolina', 'Sandra Elizabeth', 'Carmen Lorena',
            'Silvia Margarita', 'Wendy Marisol', 'Claudia Marcela', 'Karla Patricia',
            'Rosa Margarita', 'Vilma Rebeca', 'Beatriz Eugenia', 'Gabriela María'
        ];
        $titulosFemeninos = ['Inga.', 'Licda.', 'Sra.', 'Mtra.', 'Dra.'];

        $apellidosContacto = [
            'Quintanilla', 'Martínez', 'Batres', 'López', 'Rodríguez', 'Hernández', 'Flores',
            'Gómez', 'Ramos', 'Castillo', 'Villalobos', 'Portillo', 'Salmerón', 'Granados', 'Mendoza',
            'Benítez', 'Alvarado', 'Zelaya', 'Carranza', 'Guillén', 'Pérez', 'Rivera', 'Morales',
            'Aguilar', 'Romero', 'Vásquez', 'Castro', 'Chávez', 'Mejía', 'Guzmán', 'Rivas'
        ];

        $startTs = strtotime('2016-01-01 08:00:00');
        $endTs = strtotime('2026-10-11 18:00:00');
        $tsInterval = ($endTs - $startTs) / $total;

        $nombresUsados = [];
        $records = [];
        $insertedCount = 0;

        for ($i = 1; $i <= $total; $i++) {
            $attempt = 0;
            do {
                $salt = $attempt * 17;
                $idxT = ($i * 7 + $salt) % count($tiposEmpresa);
                $idxI = ($i * 13 + (int)($i / 40) + $salt * 3) % count($identidadesTerritoriales);
                $t = $tiposEmpresa[$idxT];
                $id = $identidadesTerritoriales[$idxI];
                $soc = $sociedades[($i + $attempt) % count($sociedades)];

                if ($i <= 2500) {
                    $nombreEmpresa = "{$t} {$id}, {$soc}";
                } else {
                    $idxE = ($i * 19 + (int)($i / 100) + $salt * 5) % count($especialidades);
                    $esp = $especialidades[$idxE];
                    $nombreEmpresa = "{$t} {$esp} {$id}, {$soc}";
                }
                $attempt++;
            } while (isset($nombresUsados[$nombreEmpresa]) && $attempt < 150);

            $nombresUsados[$nombreEmpresa] = true;

            // Generar correo corporativo realista
            $slugEmpresa = Str::slug(explode(',', $nombreEmpresa)[0]);
            $slugEmpresa = preg_replace('/[^a-z0-9]/', '', substr($slugEmpresa, 0, 18));
            if (empty($slugEmpresa)) {
                $slugEmpresa = 'proveedor' . $i;
            }
            $dominiosCorp = ['com.sv', 'com', 'sv', 'net.sv', 'latam.com'];
            $dominio = $dominiosCorp[$i % count($dominiosCorp)];
            $areasEmail = ['ventas', 'contacto', 'pedidos', 'info', 'administracion', 'comercial'];
            $area = $areasEmail[$i % count($areasEmail)];
            $correo = "{$area}@{$slugEmpresa}.{$dominio}";

            // Contacto ejecutivo con género y título coherente
            $esContactoFem = ($i % 2 === 0);
            if ($esContactoFem) {
                $nomC = $contactosFemeninos[($i * 11 + 3) % count($contactosFemeninos)];
                $tit = $titulosFemeninos[($i * 5) % count($titulosFemeninos)];
            } else {
                $nomC = $contactosMasculinos[($i * 11 + 3) % count($contactosMasculinos)];
                $tit = $titulosMasculinos[($i * 5) % count($titulosMasculinos)];
            }
            $apeC1 = $apellidosContacto[($i * 7 + 5) % count($apellidosContacto)];
            $apeC2 = $apellidosContacto[($i * 17 + 13) % count($apellidosContacto)];
            if ($apeC1 === $apeC2) {
                $apeC2 = $apellidosContacto[($i * 17 + 23) % count($apellidosContacto)];
            }
            $contacto = "{$tit} {$nomC} {$apeC1} {$apeC2}";

            // Dirección
            $dirBase = $direccionesBase[$i % count($direccionesBase)];
            $direccion = $dirBase;

            // NIT y NRC salvadoreños
            $nit = sprintf('%04d-%06d-%03d-%d',
                1000 + ($i % 9000),
                100000 + ($i * 7 % 900000),
                100 + ($i % 900),
                $i % 10
            );
            $nrc = sprintf('%d-%d', 10000 + ($i % 90000), $i % 10);

            // Teléfono comercial salvadoreño (2xxx-xxxx)
            $prefijos = ['2660', '2661', '2665', '2280', '2245', '2624', '2630'];
            $prefijo = $prefijos[$i % count($prefijos)];
            $telefono = sprintf('%s-%04d', $prefijo, 1000 + ($i % 8999));

            // Timestamp cronológico
            $itemTs = (int)($startTs + ($i * $tsInterval) + rand(-3600, 3600));
            if ($itemTs > $endTs) $itemTs = $endTs;
            $createdAt = date('Y-m-d H:i:s', $itemTs);

            $records[] = [
                'nombre' => $nombreEmpresa,
                'nit' => $nit,
                'nrc' => $nrc,
                'correo' => $correo,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'contacto' => $contacto,
                'estado' => ($i % 50 !== 0), // 98% activos, 2% inactivos
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($records) >= $batchSize) {
                DB::table('proveedors')->insert($records);
                $insertedCount += count($records);
                $records = [];
                $this->command->getOutput()->write("\rProveedores insertados: {$insertedCount} / {$total}");
            }
        }

        if (!empty($records)) {
            DB::table('proveedors')->insert($records);
            $insertedCount += count($records);
        }

        // Sincronizar secuencia en PostgreSQL
        DB::statement("SELECT setval('proveedors_id_seq', (SELECT MAX(id) FROM proveedors))");

        $this->command->info("\nProveedores completados exitosamente: {$insertedCount} proveedores realistas registrados.");
    }
}
