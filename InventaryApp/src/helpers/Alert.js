import { Notyf } from "notyf";
import "notyf/notyf.min.css";

const notyf = new Notyf({
  duration: 6000,
  position: {
    x: "right",
    y: "top",
  },
  types: [
    {
      type: "info",
      background: "linear-gradient(45deg, #17a2b8, #85d1e0)", // Gradiente azul intenso
      icon: {
        className: "material-icons",
        tagName: "i",
        text: "info",
      },
    },
    {
      type: "success",
      background: "linear-gradient(45deg, #28a745, #7ddf7d)", // Gradiente verde intenso
      icon: {
        className: "material-icons",
        tagName: "i",
        text: "check",
      },
    },
    {
      type: "warning",
      background: "linear-gradient(45deg, #ffc107, #ffe68a)", // Gradiente amarillo intenso
      icon: {
        className: "material-icons",
        tagName: "i",
        text: "warning",
      },
    },
    {
      type: "error",
      background: "linear-gradient(45deg, #dc3545, #ff6666)", // Gradiente rojo intenso
      icon: {
        className: "material-icons",
        tagName: "i",
        text: "error",
      },
    },
    {
      type: "productAgotado",
      background: "linear-gradient(45deg, #dc3545, #ff6666)", // Gradiente rojo intenso
      icon: {
        className: "material-icons",
        tagName: "i",
        text: "production_quantity_limits",
      },
    },
    {
      type: "productPorAgotar",
      background: "linear-gradient(45deg, #007bff, #7db9e8)", // Gradiente azul intenso,
      icon: {
        className: "material-icons",
        tagName: "i",
        text: "inventory_2",
      },
    },
  ],
});

function formatErrorMessage(msg) {
  if (typeof msg !== "string") return msg;
  return msg
    .replace(/The\s+([a-zA-Z0-9_.*]+)\s+field\s+is\s+required\.?/gi, (match, field) => {
      if (/product/i.test(field)) return "El producto es requerido.";
      return `El campo ${field} es requerido.`;
    })
    .replace(/\(and\s+(\d+)\s+more\s+errors?\)/gi, "(y $1 errores más)")
    .replace(/\(and\s+1\s+more\s+error\)/gi, "(y 1 error más)")
    .replace(/The\s+([a-zA-Z0-9_.*]+)\s+has\s+already\s+been\s+taken\.?/gi, "El valor ingresado ya está registrado.");
}

const Alert = (title, message, type) => {
  const cleanMessage = formatErrorMessage(message);
  notyf.open({
    type: type,
    message: `<b style="color: white;font-size: 18px">${title}</b><br>${cleanMessage}`,
  });
};

export default Alert;
