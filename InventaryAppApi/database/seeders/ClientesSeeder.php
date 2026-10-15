<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Genera 10,000 clientes con nombres, correos y direcciones 100% realistas para Centro de Plástico El Limeño
     */
    public function run(): void
    {
        $this->command->info('>>> [3/7] Generando 10,000 Clientes salvadoreños (Comerciales y Personas Naturales)...');

        $total = 10000;
        $batchSize = 1000;

        // Obtener IDs de usuarios (vendedores y admin)
        $userIds = DB::table('users')->pluck('id')->toArray();
        if (empty($userIds)) {
            $userIds = [1];
        }

        // Catálogo para Clientes Comerciales (25% con NRC)
        $tiposNegocio = [
            'Pupusería', 'Comedor', 'Restaurante', 'Panadería', 'Pastelería', 'Mini Súper',
            'Abarrotería', 'Variedades', 'Carnicería', 'Pollería', 'Pizzería', 'Taquería',
            'Cafetería', 'Tortillería', 'Marisquería', 'Librería', 'Farmacia', 'Distribuidora',
            'Frutería y Verdulería', 'Heladería y Paletería', 'Banquetería y Eventos', 'Rosticería',
            'Cevichería', 'Supertienda', 'Antojitos', 'Comercial', 'Surtidora', 'Boutique del Pan',
            'Golosinas y Refrescos', 'Abastecedora', 'Asados y Parrilladas', 'Dulcería Tradicional',
            'Tienda de Conveniencia', 'Empacadora Local', 'Snack y Refresquería', 'Bazar y Desechables'
        ];

        $identidadesNegocio = [
            'Doña Mary', 'Doña Rosa', 'El Buen Sabor', 'La Bendición', 'San Miguel', 'El Esfuerzo',
            'La Providencia', 'San Antonio', 'El Chaparrastique', 'La Sultana', 'El Trébol', 'Los Ángeles',
            'La Esperanza', 'El Ahorro', 'Santa Eduviges', 'La Guadalupana', 'El Triunfo', 'La Palma',
            'San José', 'El Mirador', 'Los Gemelos', 'La Familia', 'El Rinconcito', 'San Rafael',
            'La Ceiba', 'El Paraíso', 'San Judas Tadeo', 'La Prosperidad', 'El Amigo', 'La Gloria',
            'La Estrella', 'El Pacífico', 'San Francisco', 'El Progreso', 'La Migueleña', 'El Oasis',
            'San Juan', 'Las Delicias', 'La Fe', 'El Buen Pastor', 'Don Chepe', 'Doña Carmen',
            'Don Beto', 'Doña Blanca', 'Don Jorge', 'Doña Gloria', 'Don Raúl', 'Doña Silvia',
            'Don Mario', 'Doña Elena', 'Don Carlos', 'Doña Teresa', 'Doña Vilma', 'Don Fernando',
            'La Bendición de Dios', 'El Manantial', 'La Estación', 'El Diamante', 'La Gran Manzana',
            'San Benito', 'El Edén', 'La Palmera', 'Los Almendros', 'Doña Tina', 'Don Lito',
            'Doña Chilo', 'Don Paco', 'La Amistad', 'El Porvenir', 'Santa Elena'
        ];

        $ubicacionesNegocio = [
            'de Oriente', 'Roosevelt', 'San Miguel', 'Centro', 'del Parque', 'La Pradera',
            'Ciudad Jardín', 'El Calvario', 'San Felipe', 'Metrocentro', 'Barrios', 'Ruta Militar',
            'del Sol', 'y Más', 'Familiar', 'Artesanal', 'Gourmet', 'Express', 'Tradicional',
            'Campestre', 'Popular', 'Económico', 'La Paz', 'El Tamarindo', 'Moncagua', 'Chinameca',
            'Usulután', 'Gotera', 'Santa María', 'La Unión', 'El Tránsito', 'Jiquilisco',
            'Santiago de María', 'Berlín', 'Sesori', 'San Alejo', 'Intipucá', 'Conchagua'
        ];

        // Catálogo para Clientes Personas Naturales (75% sin NRC)
        $nombresM = [
            'José', 'Juan', 'Carlos', 'Luis', 'Francisco', 'Jorge', 'Manuel', 'Antonio',
            'David', 'Mario', 'Miguel', 'Roberto', 'Pedro', 'Ricardo', 'Oscar', 'Nelson',
            'Julio', 'Walter', 'Alexander', 'Kevin', 'Eduardo', 'Mauricio', 'René', 'Jaime',
            'Marcos', 'Héctor', 'Raúl', 'César', 'Rubén', 'Guillermo', 'Ernesto', 'Hugo',
            'Alonso', 'Salvador', 'Enrique', 'Armando', 'Gustavo', 'Víctor', 'Edgar', 'Wilfredo',
            'Rodrigo', 'Félix', 'Samuel', 'Daniel', 'Álvaro', 'Esteban', 'Gerardo', 'Felipe',
            'Gonzalo', 'Ramiro', 'Cristian', 'Brayan', 'Erick', 'Javier', 'Lorenzo', 'Ignacio'
        ];

        $nombresF = [
            'María', 'Rosa', 'Ana', 'Carmen', 'Silvia', 'Patricia', 'Sandra', 'Claudia',
            'Sonia', 'Karla', 'Gloria', 'Wendy', 'Jessica', 'Blanca', 'Teresa', 'Lorena',
            'Margarita', 'Beatriz', 'Marisol', 'Elizabeth', 'Gabriela', 'Vilma', 'Rebeca', 'Evelyn',
            'Mirna', 'Cecilia', 'Delmy', 'Yolanda', 'Dora', 'Lilian', 'Marta', 'Alicia',
            'Juana', 'Irma', 'Sonia', 'Norma', 'Roxana', 'Flor', 'Elsa', 'Ruth',
            'Yanira', 'Morena', 'Ingrid', 'Susana', 'Idalia', 'Lucía', 'Mercedes', 'Guadalupe',
            'Xiomara', 'Yamileth', 'Fátima', 'Adriana', 'Karina', 'Daniela', 'Natalia', 'Valeria'
        ];

        $apellidos = [
            'Hernández', 'Martínez', 'López', 'García', 'Rodríguez', 'Flores', 'Pérez',
            'González', 'Quintanilla', 'Rivera', 'Gómez', 'Díaz', 'Cruz', 'Reyes', 'Morales',
            'Aguilar', 'Ramos', 'Castillo', 'Romero', 'Ortiz', 'Castro', 'Vásquez', 'Mendoza',
            'Portillo', 'Salmerón', 'Villalobos', 'Lazo', 'Benítez', 'Granados', 'Batres',
            'Chávez', 'Alvarado', 'Zelaya', 'Carranza', 'Guillén', 'Mejía', 'Guzmán', 'Rivas',
            'Escobar', 'Navarro', 'Serrano', 'Pacheco', 'Monge', 'Campos', 'Orellana', 'Ayala',
            'Calderón', 'Pineda', 'Salgado', 'Fuentes', 'Menjívar', 'Maldonado', 'Miranda', 'Montano',
            'Barahona', 'Barillas', 'Berríos', 'Bonilla', 'Bustillo', 'Cárcamo', 'Cardoza', 'Centeno',
            'Chicas', 'Coreas', 'Cornejo', 'Coto', 'Dubón', 'Erazo', 'Galdámez', 'Guevara',
            'Jovel', 'Linares', 'Machado', 'Magaña', 'Maravilla', 'Melgar', 'Membreño', 'Merlos',
            'Montalvo', 'Montoya', 'Murillo', 'Ochoa', 'Palacios', 'Palma', 'Parada', 'Pastora',
            'Penado', 'Pleitez', 'Polanco', 'Quinteros', 'Recinos', 'Rendón', 'Rosales', 'Rubio',
            'Salinas', 'Saravia', 'Segovia', 'Solórzano', 'Sosa', 'Umaña', 'Valle', 'Ventura',
            'Villatoro', 'Zepeda'
        ];

        // Direcciones residenciales y comerciales salvadoreñas
        $sectoresSalvador = [
            'Barrio El Calvario, 2ª Calle Poniente #%d, San Miguel',
            'Colonia Ciudad Jardín, Pasaje %d Casa #%d, San Miguel',
            'Barrio San Felipe, 4ª Avenida Sur #%d, San Miguel',
            'Avenida Roosevelt Sur, Edificio Plaza Florencia Local %d, San Miguel',
            'Colonia Milagro de la Paz, Calle Principal Casa #%d, San Miguel',
            'Barrio La Cruz, 6ª Calle Oriente #%d, San Miguel',
            'Urbanización España, Polígono %s Casa #%d, San Miguel',
            'Colonia Chaparrastique, Pasaje Las Flores Casa #%d, San Miguel',
            'Avenida José Simeón Cañas #%d, San Miguel',
            'Barrio San Francisco, Calle Concepción #%d, San Miguel',
            'Colonia Santa Emilia, Senda 2 Casa #%d, San Miguel',
            'Barrio El Centro, 1ª Calle Oriente #%d, Usulután',
            'Colonia Kury, Avenida Las Margaritas #%d, San Miguel',
            'Barrio La Merced, 2ª Avenida Norte #%d, Usulután',
            'Barrio El Tránsito, Calle Gerardo Barrios #%d, San Miguel',
            'Colonia Las Delicias, Pasaje Los Almendros #%d, San Miguel',
            'Barrio Concepción, 4ª Calle Poniente #%d, La Unión',
            'Colonia San Carlos, Calle Morazán #%d, San Francisco Gotera'
        ];

        $poligonos = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'];

        $dominiosPersonas = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com'];

        $startTs = strtotime('2016-01-01 08:00:00');
        $endTs = strtotime('2026-10-11 18:00:00');
        $tsInterval = ($endTs - $startTs) / $total;

        $nombresUsados = [];
        $correosUsados = [];
        $records = [];
        $insertedCount = 0;

        for ($i = 1; $i <= $total; $i++) {
            $isNegocio = ($i % 4 === 0); // 25% negocios / contribuyentes con NRC
            $attempt = 0;

            do {
                $salt = $attempt * 13;
                if ($isNegocio) {
                    $idxN = (int)($i / 4) + $salt;
                    $t = $tiposNegocio[($idxN * 7 + $attempt) % count($tiposNegocio)];
                    $id = $identidadesNegocio[($idxN * 13 + (int)($idxN / 36) + $attempt * 5) % count($identidadesNegocio)];
                    $ub = $ubicacionesNegocio[($idxN * 29 + (int)($idxN / 120) + $attempt * 7) % count($ubicacionesNegocio)];
                    $nombre = "{$t} {$id} {$ub}";
                } else {
                    $isF = ($i % 2 === 0);
                    $namesPool = $isF ? $nombresF : $nombresM;
                    $cntN = count($namesPool);
                    $cntA = count($apellidos);

                    $n1 = $namesPool[($i * 7 + 1 + $attempt * 3) % $cntN];
                    $n2 = $namesPool[($i * 19 + 5 + (int)($i / $cntN) + $attempt * 5) % $cntN];
                    if ($n1 === $n2) {
                        $n2 = $namesPool[($i * 19 + 11 + $attempt) % $cntN];
                    }

                    $a1 = $apellidos[($i * 31 + 7 + (int)($i / 50) + $attempt * 7) % $cntA];
                    $a2 = $apellidos[($i * 73 + 23 + (int)($i / ($cntN * 2)) + $attempt * 11) % $cntA];
                    if ($a1 === $a2) {
                        $a2 = $apellidos[($i * 73 + 37 + $attempt) % $cntA];
                    }

                    if (($i + $attempt) % 7 === 0) {
                        $nombre = "{$n1} {$a1} {$a2}";
                    } elseif (($i + $attempt) % 11 === 0) {
                        $nombre = "{$n1} {$n2} {$a1}";
                    } else {
                        $nombre = "{$n1} {$n2} {$a1} {$a2}";
                    }
                }
                $attempt++;
            } while (isset($nombresUsados[$nombre]) && $attempt < 150);

            $nombresUsados[$nombre] = true;

            // Generación de correo electrónico realista y 100% único
            if ($isNegocio) {
                $slug = Str::slug($nombre);
                $slug = preg_replace('/[^a-z0-9]/', '', substr($slug, 0, 24));
                $correo = "pedidos.{$slug}.{$i}@gmail.com";
                $nrc = sprintf('%d-%d', 20000 + ($i % 70000), $i % 10);
            } else {
                $cleanN1 = preg_replace('/[^a-z]/', '', strtolower(Str::ascii($n1)));
                $cleanA1 = preg_replace('/[^a-z]/', '', strtolower(Str::ascii($a1)));
                $domPers = $dominiosPersonas[$i % count($dominiosPersonas)];
                $sufNum = ($i % 90) + 10;
                $correo = "{$cleanN1}.{$cleanA1}{$sufNum}.{$i}@{$domPers}";
                $nrc = null;
            }
            $correosUsados[$correo] = true;

            // DUI salvadoreño (8 dígitos + guion + 1 dígito)
            $dui = sprintf('%08d-%d', 10000000 + $i, ($i * 3) % 10);

            // NIT salvadoreño
            $nit = sprintf('%04d-%06d-%03d-%d',
                1200 + ($i % 200),
                100000 + ($i * 11 % 899999),
                100 + ($i % 899),
                $i % 10
            );

            // Teléfono móvil salvadoreño (7xxx-xxxx, 6xxx-xxxx) o fijo (2xxx-xxxx)
            $prefijo = ($i % 3 === 0) ? '7' : (($i % 3 === 1) ? '6' : '2');
            $telefono = sprintf('%s%03d-%04d', $prefijo, 100 + ($i % 899), 1000 + ($i % 8999));

            // Dirección realista
            $tplDir = $sectoresSalvador[$i % count($sectoresSalvador)];
            $numCasa = (($i * 3) % 450) + 12;
            $numPasaje = (($i % 8) + 1);
            $poligonoLetra = $poligonos[$i % count($poligonos)];

            if (str_contains($tplDir, '%s')) {
                $direccion = sprintf($tplDir, $poligonoLetra, $numCasa);
            } elseif (substr_count($tplDir, '%d') === 2) {
                $direccion = sprintf($tplDir, $numPasaje, $numCasa);
            } else {
                $direccion = sprintf($tplDir, $numCasa);
            }

            $assignedUserId = $userIds[$i % count($userIds)];

            // Fechas cronológicas 2016-2026
            $itemTs = (int)($startTs + ($i * $tsInterval) + rand(-3600, 3600));
            if ($itemTs > $endTs) $itemTs = $endTs;
            $createdAt = date('Y-m-d H:i:s', $itemTs);

            $records[] = [
                'nombre' => $nombre,
                'correo' => $correo,
                'dui' => $dui,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'status' => ($i % 35 !== 0), // 97% activos, 3% inactivos
                'nit' => $nit,
                'nrc' => $nrc,
                'user_id' => $assignedUserId,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($records) >= $batchSize) {
                DB::table('clientes')->insert($records);
                $insertedCount += count($records);
                $records = [];
                $this->command->getOutput()->write("\rClientes insertados: {$insertedCount} / {$total}");
            }
        }

        if (!empty($records)) {
            DB::table('clientes')->insert($records);
            $insertedCount += count($records);
        }

        // Sincronizar secuencia en PostgreSQL
        DB::statement("SELECT setval('clientes_id_seq', (SELECT MAX(id) FROM clientes))");

        $this->command->info("\nClientes completados exitosamente: {$insertedCount} clientes realistas registrados.");
    }
}
