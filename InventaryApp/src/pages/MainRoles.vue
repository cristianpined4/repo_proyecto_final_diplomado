<template>
  <q-page class="q-pa-md">
    <!-- Header Banner -->
    <q-card
      class="bg-gradient-brand text-white q-mb-md shadow-2 rounded-card overflow-hidden"
    >
      <q-card-section class="q-py-md">
        <div class="row items-center justify-between no-wrap">
          <div class="row items-center">
            <q-avatar
              icon="security"
              size="48px"
              class="q-mr-md bg-white-translucent text-white"
            />
            <div>
              <div class="text-h5 text-weight-bold">Roles y Permisos</div>
              <div class="text-subtitle2 opacity-80">
                Define los roles de trabajo y asigna los accesos autorizados
                para cada sección del sistema
              </div>
            </div>
          </div>
          <q-btn
            v-if="authStore.hasPermission('roles.crear')"
            color="white"
            text-color="primary"
            label="Nuevo Rol"
            icon="add"
            unelevated
            class="rounded-btn q-px-md text-weight-bold"
            @click="openModal(null)"
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- ROLES TABLE -->
    <q-card flat bordered class="rounded-card overflow-hidden bg-white">
      <q-table
        :loading="loading"
        :rows="roles"
        :columns="columns"
        row-key="id"
        flat
        class="no-shadow"
        :pagination="{ rowsPerPage: 10 }"
        no-data-label="No hay roles disponibles"
      >
        <template v-slot:body-cell-permisos="props">
          <q-td :props="props" class="text-center">
            <q-chip
              color="purple-2"
              text-color="purple-9"
              dense
              class="text-weight-bold"
            >
              {{ props.row.permissions ? props.row.permissions.length : 0 }}
              Permisos
            </q-chip>
          </q-td>
        </template>

        <template v-slot:body-cell-options="props">
          <q-td :props="props" align="right">
            <q-btn-dropdown
              v-if="!isDefaultRole(props.row.name) && (authStore.hasPermission('roles.editar') || authStore.hasPermission('roles.eliminar'))"
              flat
              color="dark"
              icon="more_vert"
              size="md"
            >
              <q-list>
                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('roles.editar')"
                  @click="openModal(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="edit" color="primary" />
                  </q-item-section>
                  <q-item-section> Editar </q-item-section>
                </q-item>

                <q-item
                  clickable
                  v-close-popup
                  v-if="authStore.hasPermission('roles.eliminar')"
                  @click="confirmDelete(props.row)"
                >
                  <q-item-section avatar>
                    <q-icon name="delete" color="negative" />
                  </q-item-section>
                  <q-item-section> Eliminar </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
            <span v-else class="text-caption text-grey-5">—</span>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- DIALOG DE ROL & PERMISOS -->
    <q-dialog v-model="modalOpen" persistent max-width="800px" width="100%">
      <q-card
        class="dialog-card shadow-15"
        style="border-radius: 12px; overflow: hidden"
      >
        <q-form ref="roleForm" @submit.prevent="saveRole">
          <q-card-section class="bg-primary text-white q-py-md">
            <div class="text-h6 text-weight-bold row items-center">
              <q-icon name="admin_panel_settings" class="q-mr-xs" />
              {{ editRole ? "Editar Rol" : "Crear Nuevo Rol" }}
            </div>
            <div class="text-caption opacity-80">
              Asigna un nombre único y marca los permisos requeridos
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-gutter-y-sm">
            <q-input
              v-model="form.name"
              label="Nombre del Rol (Ej: Supervisor, Cajero)"
              filled
              :rules="[(val) => !!val || 'El nombre es obligatorio']"
              class="rounded-input"
              :disable="editRole && isDefaultRole(editRole.name)"
            />

            <div class="text-subtitle2 text-weight-bold text-grey-8 q-mt-md">
              Permisos Asignados por Sección
            </div>
            <q-separator class="q-mb-md" />

            <!-- LISTA DE PERMISOS AGRUPADOS -->
            <div
              class="row q-col-gutter-md scroll-container"
              style="max-height: 400px; overflow-y: auto"
            >
              <div
                v-for="section in permissionsList"
                :key="section.seccion"
                class="col-12"
              >
                <q-card bordered flat class="rounded-card q-pa-sm bg-grey-1">
                  <div class="text-weight-bold text-blue-10 q-mb-xs q-px-sm">
                    Sección: {{ section.seccion }}
                  </div>
                  <q-separator class="q-mb-sm" />
                  <div class="row q-col-gutter-sm">
                    <div
                      v-for="perm in section.permisos"
                      :key="perm.id"
                      class="col-6 col-sm-3"
                    >
                      <q-checkbox
                        v-model="form.permissions"
                        :val="perm.name"
                        :label="perm.action.toUpperCase()"
                        color="primary"
                      />
                    </div>
                  </div>
                </q-card>
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-2">
            <q-btn
              flat
              label="Cancelar"
              color="grey-7"
              v-close-popup
              class="rounded-btn"
            />
            <q-btn
              type="submit"
              label="Guardar Rol"
              color="primary"
              unelevated
              class="q-px-md rounded-btn"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- ELIMINAR CONFIRM -->
    <q-dialog v-model="deleteOpen" persistent>
      <q-card style="border-radius: 12px">
        <q-card-section class="row items-center q-pb-none">
          <q-avatar
            icon="warning"
            color="warning"
            text-color="white"
            class="q-mr-sm"
          />
          <span class="text-weight-bold text-h6">Confirmar Eliminación</span>
        </q-card-section>
        <q-card-section class="q-pt-md">
          ¿Estás seguro de que deseas eliminar permanentemente el rol
          <strong>{{ roleToDelete?.name }}</strong
          >? Esta acción no se puede deshacer.
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn
            flat
            label="Cancelar"
            color="grey-7"
            v-close-popup
            class="rounded-btn"
          />
          <q-btn
            label="Eliminar"
            color="negative"
            unelevated
            class="rounded-btn q-px-md"
            @click="deleteRole"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { useAuthStore } from "src/store/auth-store";
import { api } from "boot/axios";
import { useLoadingStore } from "src/store/loading-store";
import Alert from "src/helpers/Alert";

export default {
  computed: {
    authStore() {
      return useAuthStore();
    },
  },
  data() {
    return {
      loading: false,
      roles: [],
      permissionsList: [],
      modalOpen: false,
      deleteOpen: false,
      editRole: null,
      roleToDelete: null,
      columns: [
        { name: "name", label: "Rol / Perfil", align: "left", field: "name" },
        { name: "permisos", label: "Permisos", align: "center" },
        { name: "options", label: "Acciones", align: "right" },
      ],
      form: {
        name: "",
        permissions: [],
      },
    };
  },
  created() {
    this.fetchRoles();
    this.fetchPermissions();
  },
  methods: {
    isDefaultRole(name) {
      return ["admin", "gerente", "vendedor", "almacenero"].includes(name);
    },
    async fetchRoles() {
      this.loading = true;
      try {
        const response = await api.get("/api/roles");
        this.roles = response.data.data;
      } catch (err) {
        Alert("Error", "Error al obtener roles del sistema", "error");
      } finally {
        this.loading = false;
      }
    },
    async fetchPermissions() {
      try {
        const response = await api.get("/api/roles/permissions");
        this.permissionsList = response.data.data;
      } catch (err) {
        console.error("Error al cargar listado de permisos", err);
      }
    },
    openModal(row) {
      if (row && !this.authStore.hasPermission("roles.editar")) {
        Alert("Acceso denegado", "No tienes permiso para editar roles.", "warning");
        return;
      }
      if (!row && !this.authStore.hasPermission("roles.crear")) {
        Alert("Acceso denegado", "No tienes permiso para crear roles.", "warning");
        return;
      }
      if (row) {
        this.editRole = row;
        this.form = {
          name: row.name,
          permissions: row.permissions
            ? row.permissions.map((p) => p.name)
            : [],
        };
      } else {
        this.editRole = null;
        this.form = {
          name: "",
          permissions: [],
        };
      }
      this.modalOpen = true;
    },
    async saveRole() {
      const isValid = await this.$refs.roleForm.validate();
      if (!isValid) return;

      Alert("Guardando...", "Guardando los permisos del rol...", "info");
      useLoadingStore().startLoading();
      try {
        if (this.editRole) {
          await api.put(`/api/roles/${this.editRole.id}`, this.form);
          Alert("Éxito", "Rol actualizado correctamente", "success");
        } else {
          await api.post("/api/roles", this.form);
          Alert("Éxito", "Rol creado correctamente", "success");
        }
        this.modalOpen = false;
        this.fetchRoles();
      } catch (err) {
        Alert(
          "Error",
          err.response?.data?.message || "No se pudo guardar el rol",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
    confirmDelete(row) {
      if (!this.authStore.hasPermission("roles.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar roles.", "warning");
        return;
      }
      this.roleToDelete = row;
      this.deleteOpen = true;
    },
    async deleteRole() {
      if (!this.authStore.hasPermission("roles.eliminar")) {
        Alert("Acceso denegado", "No tienes permiso para eliminar roles.", "warning");
        return;
      }
      useLoadingStore().startLoading();
      try {
        await api.delete(`/api/roles/${this.roleToDelete.id}`);
        Alert("Éxito", "Rol eliminado correctamente", "success");
        this.deleteOpen = false;
        this.fetchRoles();
      } catch (err) {
        Alert(
          "Error",
          err.response?.data?.message || "Error al eliminar el rol",
          "error"
        );
      } finally {
        useLoadingStore().stopLoading();
      }
    },
  },
};
</script>

<style scoped>
.rounded-card {
  border-radius: 12px;
}

.dialog-card {
  border-radius: 12px;
  overflow: hidden;
}

.rounded-btn {
  border-radius: 8px;
  text-transform: none;
}

.rounded-input {
  border-radius: 8px;
}

.scroll-container::-webkit-scrollbar {
  width: 6px;
}
.scroll-container::-webkit-scrollbar-thumb {
  background-color: #ccc;
  border-radius: 4px;
}

.bg-white-translucent {
  background-color: rgba(255, 255, 255, 0.15);
}

/* Custom Table Styles */
:deep(.q-table__card) {
  box-shadow: none;
}

:deep(.q-table th) {
  font-weight: 700;
  background-color: #f5f5f5;
  color: #333;
}

:deep(.q-table tbody tr:hover) {
  background-color: rgba(var(--q-primary-rgb), 0.04);
}
</style>
